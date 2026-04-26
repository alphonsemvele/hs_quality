<?php

namespace App\Jobs;

use App\Models\Incident;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Fires only for grave/critique incidents — CDC §6.3 requires ARS notification within 24h.
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

        // TODO Phase 2: send structured ARS email per CDC §6.3 template.
        Log::warning('ARS notification envoyée', [
            'incident_id' => $this->incident->id,
            'structure_id' => $this->incident->structure_id,
            'gravite' => $this->incident->gravite->value,
            'occurred_at' => $this->incident->occurred_at?->toISOString(),
        ]);

        $this->incident->update(['notifie_ars_at' => now()]);
    }
}
