<?php

namespace App\Jobs;

use App\Models\Incident;
use App\Notifications\Incidents\IncidentArsNotification;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\CircuitBreaker\CircuitOpenException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

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
                $arsEmail = config('incidents.ars.email');
                $arsEnabled = (bool) config('incidents.ars.enabled', false);

                if ($arsEnabled === true && is_string($arsEmail) && $arsEmail !== '') {
                    // Forward through the framework's notification router so
                    // the same Notification class is testable via the standard
                    // Notification::fake() facade. Failures propagate up so
                    // the circuit breaker sees them.
                    Notification::route('mail', $arsEmail)
                        ->notify(new IncidentArsNotification($this->incident));
                } else {
                    Log::warning('ARS notification désactivée — config absente', [
                        'incident_id' => $this->incident->id,
                        'structure_id' => $this->incident->structure_id,
                        'has_email' => is_string($arsEmail) && $arsEmail !== '',
                        'enabled' => $arsEnabled,
                    ]);
                }

                Log::info('ARS notification traitée', [
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
