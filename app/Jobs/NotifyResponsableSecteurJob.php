<?php

namespace App\Jobs;

use App\Models\Incident;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyResponsableSecteurJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly Incident $incident) {}

    public function handle(): void
    {
        // Guard against double-firing if job is replayed after a partial failure.
        if ($this->incident->notifie_responsable_at !== null) {
            return;
        }

        // TODO Phase 2: send structured email via SES / Brevo.
        // Coordinator/responsable email = $this->incident->structure->responsable_email
        Log::info('Responsable secteur notifié', [
            'incident_id' => $this->incident->id,
            'structure_id' => $this->incident->structure_id,
            'gravite' => $this->incident->gravite->value,
        ]);

        $this->incident->update(['notifie_responsable_at' => now()]);
    }
}
