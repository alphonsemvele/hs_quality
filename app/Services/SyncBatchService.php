<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QvctExchangeAddresseeRole;
use App\Enums\QvctMood;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\Incident;
use App\Models\Intervention;
use App\Models\QvctCampaign;
use App\Models\QvctExchangeRequest;
use App\Models\QvctJournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Processes a batch of queued mobile operations posted by an offline-first
 * client when network returns. Each operation is dispatched to the same
 * domain service the always-online controllers use — there is no
 * sync-specific business logic, only routing + per-op error isolation.
 *
 * Failure isolation: each op runs in its own try/catch so a failure in
 * op N never prevents ops N+1..M from running. The response carries a
 * per-op `status` (success / conflict / error) and the server's view of
 * the resource so the mobile client can reconcile.
 *
 * Idempotency: when the same op (same `client_op_id`, same actor) arrives
 * twice (network retry, app restart) the operation runs at most once.
 * Dedup is keyed on (user_id, client_op_id) for the request lifetime —
 * persistent dedup belongs to the caller via HandleIdempotency middleware
 * keyed on the batch envelope.
 *
 * Phase 1 + Phase 2 / M3 supports six ops:
 *   - intervention.check_in / check_out / cancel / submit_report
 *   - incident.create
 *   - qvct.submit_response (anonymous; tenant-scoped via campaign FK)
 * Photos / signatures are NOT batchable (binary blobs); the mobile app
 * uploads those one-by-one to the existing endpoints once online.
 *
 * Concurrency: free-text fields (intervention.report_text) are merged via
 * git-style conflict markers when two writers arrive with different
 * non-empty values. See InterventionService::mergeReportText() — same
 * helper is used by direct check_out and report submission paths so the
 * sync layer behaves identically to always-online clients.
 */
class SyncBatchService
{
    public function __construct(
        private readonly InterventionService $interventions,
        private readonly IncidentService $incidents,
        private readonly QvctService $qvct,
        private readonly JournalEntryService $journal,
        private readonly ExchangeRequestService $exchanges,
        private readonly AuditExecutionService $audits,
    ) {}

    /**
     * @param  array<int, array{client_op_id: string, kind: string, resource_id?: ?string, client_timestamp?: ?string, payload: array<string, mixed>}>  $operations
     * @return array<int, array{client_op_id: string, status: string, kind: string, resource_id?: ?string, server_state?: array<string, mixed>, error?: ?string}>
     */
    public function process(array $operations, User $actor): array
    {
        $results = [];
        $seen = [];

        foreach ($operations as $op) {
            $clientOpId = (string) $op['client_op_id'];

            // In-batch dedup: same client_op_id appearing twice in the same
            // batch is a client bug. Replay the first result for both.
            if (isset($seen[$clientOpId])) {
                $results[] = $seen[$clientOpId];

                continue;
            }

            $result = $this->runOne($op, $actor);
            $seen[$clientOpId] = $result;
            $results[] = $result;
        }

        return $results;
    }

    /**
     * @param  array{client_op_id: string, kind: string, resource_id?: ?string, payload: array<string, mixed>}  $op
     * @return array{client_op_id: string, status: string, kind: string, resource_id: ?string, server_state?: array<string, mixed>, error?: string}
     */
    private function runOne(array $op, User $actor): array
    {
        $base = [
            'client_op_id' => $op['client_op_id'],
            'kind' => $op['kind'],
            'resource_id' => $op['resource_id'] ?? null,
        ];

        try {
            $serverState = match ($op['kind']) {
                'intervention.check_in' => $this->interventionCheckIn($op, $actor),
                'intervention.check_out' => $this->interventionCheckOut($op, $actor),
                'intervention.cancel' => $this->interventionCancel($op, $actor),
                'intervention.submit_report' => $this->interventionSubmitReport($op, $actor),
                'incident.create' => $this->incidentCreate($op, $actor),
                'qvct.submit_response' => $this->qvctSubmitResponse($op, $actor),
                'qvct.write_journal' => $this->qvctWriteJournal($op, $actor),
                'qvct.request_exchange' => $this->qvctRequestExchange($op, $actor),
                'audit.record_response' => $this->auditRecordResponse($op, $actor),
                default => throw new HttpException(400, 'Unknown operation kind: '.$op['kind']),
            };

            return [...$base, 'status' => 'success', 'server_state' => $serverState];
        } catch (HttpException $e) {
            // 409 = conflict (state pre-condition failed; client should reconcile).
            // 403/404 = authz/missing (client likely has stale or wrong-tenant data).
            // Anything else surfaces as 'error' for client logging.
            $status = match (true) {
                $e->getStatusCode() === 409 => 'conflict',
                in_array($e->getStatusCode(), [403, 404], true) => 'rejected',
                default => 'error',
            };

            $serverState = $this->safeServerState($op);

            return [
                ...$base,
                'status' => $status,
                'error' => $e->getMessage(),
                ...($serverState !== null ? ['server_state' => $serverState] : []),
            ];
        } catch (ValidationException $e) {
            return [
                ...$base,
                'status' => 'error',
                'error' => 'Validation failed: '.implode('; ', collect($e->errors())->flatten()->all()),
            ];
        } catch (Throwable $e) {
            // Unexpected error — log for ops, return generic message to client.
            Log::error('sync.batch op failed unexpectedly', [
                'kind' => $op['kind'],
                'client_op_id' => $op['client_op_id'],
                'actor_id' => $actor->id,
                'exception' => $e->getMessage(),
            ]);

            return [...$base, 'status' => 'error', 'error' => 'Internal error processing this operation.'];
        }
    }

    /**
     * @param  array{resource_id?: ?string, payload: array<string, mixed>}  $op
     * @return array<string, mixed>
     */
    private function interventionCheckIn(array $op, User $actor): array
    {
        $intervention = $this->resolveIntervention($op);
        $this->authorize($actor, 'update', $intervention);

        $fresh = $this->interventions->checkIn($intervention, [
            'latitude' => $op['payload']['latitude'] ?? null,
            'longitude' => $op['payload']['longitude'] ?? null,
        ]);

        return $this->interventionState($fresh);
    }

    /**
     * @param  array{resource_id?: ?string, payload: array<string, mixed>}  $op
     * @return array<string, mixed>
     */
    private function interventionCheckOut(array $op, User $actor): array
    {
        $intervention = $this->resolveIntervention($op);
        $this->authorize($actor, 'update', $intervention);

        $fresh = $this->interventions->checkOut($intervention, [
            'report_text' => $op['payload']['report_text'] ?? null,
        ]);

        return $this->interventionState($fresh);
    }

    /**
     * @param  array{resource_id?: ?string, payload: array<string, mixed>}  $op
     * @return array<string, mixed>
     */
    private function interventionCancel(array $op, User $actor): array
    {
        $intervention = $this->resolveIntervention($op);
        $this->authorize($actor, 'update', $intervention);

        $reason = (string) ($op['payload']['reason'] ?? '');
        if ($reason === '') {
            throw new HttpException(422, 'A cancel operation requires a reason.');
        }

        $fresh = $this->interventions->cancel($intervention, $reason);

        return $this->interventionState($fresh);
    }

    /**
     * @param  array{resource_id?: ?string, payload: array<string, mixed>}  $op
     * @return array<string, mixed>
     */
    private function interventionSubmitReport(array $op, User $actor): array
    {
        $intervention = $this->resolveIntervention($op);
        $this->authorize($actor, 'submitReport', $intervention);

        $reportText = (string) ($op['payload']['report_text'] ?? '');
        if ($reportText === '') {
            throw new HttpException(422, 'A submit_report op requires a non-empty report_text.');
        }

        $fresh = $this->interventions->submitReport($intervention, $reportText, $actor);

        return $this->interventionState($fresh);
    }

    /**
     * @param  array{resource_id?: ?string, payload: array<string, mixed>}  $op
     * @return array<string, mixed>
     */
    private function qvctSubmitResponse(array $op, User $actor): array
    {
        $campaignId = $op['resource_id'] ?? null;
        if ($campaignId === null || $campaignId === '') {
            throw new HttpException(422, 'resource_id (campaign id) is required for qvct.submit_response.');
        }

        $campaign = QvctCampaign::query()->find($campaignId);
        if ($campaign === null) {
            throw new HttpException(404, "Campaign {$campaignId} not found in your tenant.");
        }

        $this->authorize($actor, 'respond', $campaign);

        $answers = $op['payload']['answers'] ?? null;
        if (! is_array($answers) || $answers === []) {
            throw new HttpException(422, 'A qvct.submit_response op requires non-empty answers.');
        }

        $response = $this->qvct->recordResponse(
            $campaign,
            $answers,
            teamTag: $op['payload']['team_tag'] ?? null,
        );

        // Anonymity: never echo the response id back. The mobile client
        // only needs to know "the server accepted my submission" to drop
        // it from the offline queue.
        return [
            'campaign_id' => $campaign->id,
            'submitted_at' => $response->submitted_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array{payload: array<string, mixed>}  $op
     * @return array<string, mixed>
     */
    private function qvctWriteJournal(array $op, User $actor): array
    {
        $this->authorize($actor, 'create', QvctJournalEntry::class);

        $body = (string) ($op['payload']['body'] ?? '');
        $moodValue = (string) ($op['payload']['mood'] ?? '');
        if ($body === '' || $moodValue === '') {
            throw new HttpException(422, 'A qvct.write_journal op requires non-empty body and mood.');
        }

        $mood = QvctMood::tryFrom($moodValue);
        if ($mood === null) {
            throw new HttpException(422, 'Invalid mood value.');
        }

        $entry = $this->journal->write(
            $actor,
            $body,
            $mood,
            (bool) ($op['payload']['shared_with_rh'] ?? false),
        );

        return [
            'id' => $entry->id,
            'mood' => $entry->mood->value,
            'shared_with_rh' => $entry->shared_with_rh,
            'created_at' => $entry->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array{payload: array<string, mixed>}  $op
     * @return array<string, mixed>
     */
    private function qvctRequestExchange(array $op, User $actor): array
    {
        $this->authorize($actor, 'create', QvctExchangeRequest::class);

        $addresseeRoleValue = (string) ($op['payload']['addressee_role'] ?? '');
        $addressee = QvctExchangeAddresseeRole::tryFrom($addresseeRoleValue);
        if ($addressee === null) {
            throw new HttpException(422, 'A qvct.request_exchange op requires a valid addressee_role.');
        }

        $exchange = $this->exchanges->create(
            $actor,
            $addressee,
            $op['payload']['message'] ?? null,
        );

        return [
            'id' => $exchange->id,
            'addressee_role' => $exchange->addressee_role->value,
            'status' => $exchange->status->value,
            'created_at' => $exchange->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array{resource_id?: ?string, payload: array<string, mixed>}  $op
     * @return array<string, mixed>
     */
    private function auditRecordResponse(array $op, User $actor): array
    {
        $runId = $op['resource_id'] ?? null;
        if ($runId === null || $runId === '') {
            throw new HttpException(422, 'resource_id (audit run id) is required for audit.record_response.');
        }

        $run = AuditRun::query()->find($runId);
        if ($run === null) {
            throw new HttpException(404, "Audit run {$runId} not found in your tenant.");
        }

        $itemId = (string) ($op['payload']['audit_grid_item_id'] ?? '');
        if ($itemId === '') {
            throw new HttpException(422, 'payload.audit_grid_item_id is required for audit.record_response.');
        }

        $item = AuditGridItem::query()->find($itemId);
        if ($item === null) {
            throw new HttpException(404, "Audit grid item {$itemId} not found in your tenant.");
        }

        $this->authorize($actor, 'create', AuditRunResponse::class);

        $response = $this->audits->recordResponse(
            $run,
            $item,
            isset($op['payload']['score']) ? (float) $op['payload']['score'] : null,
            $op['payload']['comment'] ?? null,
            $op['payload']['evidence_url'] ?? null,
            $actor,
        );

        return [
            'id' => $response->id,
            'audit_run_id' => $run->id,
            'audit_grid_item_id' => $item->id,
            'score' => $response->score !== null ? (float) $response->score : null,
            'recorded_at' => $response->recorded_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array{payload: array<string, mixed>}  $op
     * @return array<string, mixed>
     */
    private function incidentCreate(array $op, User $actor): array
    {
        $this->authorize($actor, 'create', Incident::class);

        $incident = $this->incidents->declare($op['payload'], $actor);

        return $this->incidentState($incident);
    }

    private function resolveIntervention(array $op): Intervention
    {
        $id = $op['resource_id'] ?? null;
        if ($id === null || $id === '') {
            throw new HttpException(422, 'resource_id is required for intervention.* operations.');
        }

        $intervention = Intervention::query()->find($id);
        if ($intervention === null) {
            throw new HttpException(404, "Intervention {$id} not found in your tenant.");
        }

        return $intervention;
    }

    /**
     * @return array<string, mixed>
     */
    private function interventionState(Intervention $i): array
    {
        return [
            'id' => $i->id,
            'status' => $i->status->value,
            'actual_start_at' => $i->actual_start_at?->toIso8601String(),
            'actual_end_at' => $i->actual_end_at?->toIso8601String(),
            'cancellation_reason' => $i->cancellation_reason,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function incidentState(Incident $i): array
    {
        return [
            'id' => $i->id,
            'gravite' => $i->gravite->value,
            'statut' => $i->statut->value,
            'occurred_at' => $i->occurred_at?->toIso8601String(),
        ];
    }

    /**
     * Best-effort current server state for the failed op so the mobile
     * client can reconcile its local copy. Returns null if the resource
     * can't be found / isn't applicable.
     *
     * @return array<string, mixed>|null
     */
    private function safeServerState(array $op): ?array
    {
        $id = $op['resource_id'] ?? null;
        if ($id === null) {
            return null;
        }

        if (str_starts_with($op['kind'], 'intervention.')) {
            $i = Intervention::query()->find($id);

            return $i ? $this->interventionState($i) : null;
        }

        if (str_starts_with($op['kind'], 'incident.')) {
            $i = Incident::query()->find($id);

            return $i ? $this->incidentState($i) : null;
        }

        return null;
    }

    private function authorize(User $actor, string $ability, mixed $resource): void
    {
        if (! Gate::forUser($actor)->allows($ability, $resource)) {
            throw new HttpException(403, "Not authorized to {$ability} this resource.");
        }
    }
}
