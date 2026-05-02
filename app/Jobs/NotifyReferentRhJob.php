<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\QvctWeakSignal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Fires once per emitted weak signal so the référent RH gets notified.
 * Spec: PHASE2_PROGRESS.md M3.9 + IMPLEMENTATION_PLAN line 437 "alerts
 * to référent RH".
 *
 * Idempotent: dispatched after a signal row is created. If the queue
 * worker retries, the log line is repeated but no duplicate-side-effect
 * occurs (notification channel will gain its own dedup once Phase 2
 * mail/SMS lands; for now it logs).
 *
 * Tenant context: the signal carries structure_id; the eventual mail
 * recipient lookup will scope by that.
 */
class NotifyReferentRhJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly QvctWeakSignal $signal) {}

    public function handle(): void
    {
        // TODO Phase 2 / M3 follow-up: route to actual referent_rh users
        // of the signal's structure via Notification facade once the
        // mail templates land.
        Log::info('QVCT weak signal — référent RH notification', [
            'signal_id' => $this->signal->id,
            'structure_id' => $this->signal->structure_id,
            'campaign_id' => $this->signal->campaign_id,
            'team_tag' => $this->signal->team_tag,
            'signal_type' => $this->signal->signal_type->value,
            'severity' => $this->signal->severity,
            'mean_score' => $this->signal->details['mean_score'] ?? null,
        ]);
    }
}
