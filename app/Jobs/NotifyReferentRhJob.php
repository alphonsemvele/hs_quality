<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\QvctWeakSignal;
use App\Models\User;
use App\Notifications\WeakSignalDetectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

/**
 * Fires once per emitted weak signal so the référent RH (and
 * structure-level escalation roles: dirigeant + référent qualité)
 * gets notified by mail. Spec: PHASE2_PROGRESS.md M3.9 +
 * IMPLEMENTATION_PLAN line 437 "alerts to référent RH".
 *
 * Idempotent at the dispatch level: WeakSignalDetector emits one
 * signal row per (campaign, team, signal_type) and dispatches this
 * job afterCommit(). If the queue worker retries this job, the mail
 * re-sends — acceptable since weak signals are low-frequency events,
 * not bursts. A formal dedup ledger would only matter at much higher
 * volumes; not currently warranted.
 *
 * Tenant context: queue workers run outside any HTTP-bound tenant.
 * recipients() sets Spatie's team_id explicitly so the role lookup
 * resolves to users in the signal's structure rather than failing
 * silently or leaking across tenants.
 */
class NotifyReferentRhJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly QvctWeakSignal $signal) {}

    public function handle(): void
    {
        Log::info('QVCT weak signal — référent RH notification', [
            'signal_id' => $this->signal->id,
            'structure_id' => $this->signal->structure_id,
            'campaign_id' => $this->signal->campaign_id,
            'team_tag' => $this->signal->team_tag,
            'signal_type' => $this->signal->signal_type->value,
            'severity' => $this->signal->severity,
            'mean_score' => $this->signal->details['mean_score'] ?? null,
        ]);

        $recipients = $this->recipients();
        if ($recipients->isNotEmpty()) {
            Notification::send(
                $recipients,
                new WeakSignalDetectedNotification($this->signal),
            );
        }
    }

    /**
     * Resolve recipients in the signal's structure: rh + dirigeant +
     * referent_qualite. Spatie's team scope is set explicitly so the
     * role query works in any execution context (queue worker, console
     * command, HTTP request).
     */
    private function recipients(): Collection
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->signal->structure_id);

        return User::query()
            ->where('structure_id', $this->signal->structure_id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', [
                'rh', 'dirigeant', 'referent_qualite',
            ]))
            ->get();
    }
}
