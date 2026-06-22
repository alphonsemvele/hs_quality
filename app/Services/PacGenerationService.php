<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditItemScale;
use App\Enums\PacActionStatus;
use App\Enums\PacStatus;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\Pac;
use App\Models\PacAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Auto-generate a PAC (Plan d'Amélioration Continue) from a finalised
 * audit run. Spec: PHASE2_PROGRESS.md M6.13 + IMPLEMENTATION_PLAN line
 * "PAC auto-generation from gaps".
 *
 * Algorithm:
 *   - Walk every AuditRunResponse for the run
 *   - For each whose score < max_points × THRESHOLD, emit a PacAction
 *     with source_audit_response_id pointing back at the gap
 *   - Idempotent at the (pac_id, source_audit_response_id) level:
 *     re-running the service updates the existing actions (status,
 *     responsible) is preserved; missing actions are created; nothing
 *     is duplicated
 *
 * Threshold tunable via THRESHOLD constant — 0.5 (50%) covers the
 * common case where any score below half the item's max_points
 * counts as a gap requiring action.
 */
class PacGenerationService
{
    /** Score must be ≥ THRESHOLD × max_points to NOT count as a gap. */
    public const THRESHOLD = 0.5;

    public function generateForRun(AuditRun $run, User $author): Pac
    {
        if (! $run->isFinalised()) {
            throw new HttpException(409, 'PAC can only be generated from a finalised audit run.');
        }

        return DB::transaction(function () use ($run, $author): Pac {
            $pac = Pac::query()
                ->where('audit_run_id', $run->id)
                ->first();

            if ($pac === null) {
                $pac = Pac::create([
                    'structure_id' => $run->structure_id,
                    'audit_run_id' => $run->id,
                    'title' => 'PAC — '.$run->title,
                    'description' => 'Plan d\'Amélioration Continue généré automatiquement à partir de l\'évaluation '
                        .$run->title.' du '.$run->run_date?->format('Y-m-d').'.',
                    'status' => PacStatus::Draft->value,
                    'created_by' => $author->id,
                ]);
            }

            $gaps = $this->gapResponses($run);
            $existingByResponseId = $pac->actions()
                ->whereNotNull('source_audit_response_id')
                ->get()
                ->keyBy('source_audit_response_id');

            foreach ($gaps as $response) {
                if ($existingByResponseId->has($response->id)) {
                    continue; // already actioned — preserve operator state
                }

                $priority = $response->item->priorityFor($response->cotation);

                PacAction::create([
                    'structure_id' => $run->structure_id,
                    'pac_id' => $pac->id,
                    'source_audit_response_id' => $response->id,
                    'title' => 'Action: '.$response->item->title,
                    'description' => $this->describeGap($response),
                    'status' => PacActionStatus::Pending->value,
                    'priority' => $priority,
                ]);
            }

            return $pac->fresh();
        });
    }

    /**
     * @return Collection<int, AuditRunResponse>
     */
    private function gapResponses(AuditRun $run): Collection
    {
        return AuditRunResponse::query()
            ->where('audit_run_id', $run->id)
            ->with('item')
            ->get()
            ->filter(fn (AuditRunResponse $r) => $this->isGap($r))
            ->values();
    }

    /**
     * Scale-aware gap detection:
     *   - HasCotation: any response cotation C or D is a gap (matches the
     *     "Plan d'action" sheet of the SAP grid). NA is never a gap.
     *   - Other scales: legacy semantics, score < THRESHOLD × max_points.
     */
    private function isGap(AuditRunResponse $r): bool
    {
        if ($r->item === null) {
            return false;
        }

        if ($r->item->scale === AuditItemScale::HasCotation) {
            return in_array($r->cotation, ['C', 'D'], true);
        }

        return $r->score !== null
            && (float) $r->score < self::THRESHOLD * (float) $r->item->max_points;
    }

    private function describeGap(AuditRunResponse $r): string
    {
        if ($r->item?->scale === AuditItemScale::HasCotation) {
            return trim(sprintf(
                'Cotation: %s. %s',
                (string) $r->cotation,
                (string) ($r->comment ?? ''),
            ));
        }

        return trim(sprintf(
            'Score: %.2f / %.2f. %s',
            (float) $r->score,
            (float) ($r->item?->max_points ?? 0),
            (string) ($r->comment ?? ''),
        ));
    }
}
