<?php

namespace App\Jobs;

use App\Models\Incident;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\CircuitBreaker\CircuitOpenException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Fires only for grave/critique incidents — CDC §6.3 requires ARS notification within 24h.
 *
 * The outbound call is wrapped in the `ars` circuit breaker (Phase 2 / E2).
 * When the ARS endpoint flaps, the breaker opens after 3 consecutive
 * failures and stays open for 5 minutes. While open, the job releases
 * itself back to the queue with the cooldown delay rather than burning
 * its 3 retry attempts against a known-bad endpoint.
 */
class NotifyARSJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly Incident $incident) {}

    public function handle(): void
    {
        if (! $this->incident->requiresARSNotification()) {
            return;
        }

        // Idempotency guard — safe to re-queue after transient failure.
        if ($this->incident->notifie_ars_at !== null) {
            return;
        }

        $breaker = CircuitBreaker::for('ars');

        try {
            $breaker->call(function (): void {
                // TODO Phase 2: send structured ARS email per CDC §6.3 template.
                // When the real ARS HTTP/SMTP submission lands here, the
                // breaker already wraps it. Failures from the real call
                // bubble out and increment the breaker's failure counter.
                Log::warning('ARS notification envoyée', [
                    'incident_id' => $this->incident->id,
                    'structure_id' => $this->incident->structure_id,
                    'gravite' => $this->incident->gravite->value,
                    'occurred_at' => $this->incident->occurred_at?->toISOString(),
                ]);

                $this->incident->update(['notifie_ars_at' => now()]);
            });
        } catch (CircuitOpenException $e) {
            Log::warning('ARS notification deferred — circuit open', [
                'incident_id' => $this->incident->id,
                'structure_id' => $this->incident->structure_id,
                'cooldown_seconds' => $breaker->cooldownSeconds(),
            ]);

            // Release back to the queue with the cooldown delay instead
            // of burning a retry attempt against an open circuit. The
            // 24h CDC deadline still has plenty of headroom at 5 min
            // backoffs.
            $this->release($breaker->cooldownSeconds());
        }
    }
}
