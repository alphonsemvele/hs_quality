<?php

namespace App\Jobs;

use App\Models\Incident;
use App\Models\User;
use App\Notifications\Incidents\IncidentSeverelyDeclaredNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

/**
 * Fans out the IncidentSeverelyDeclaredNotification to the structure's
 * responsable surface (dirigeant + coordinateur + référent qualité).
 * Idempotent on the Incident's notifie_responsable_at timestamp so
 * Stripe-style retry semantics never double-deliver.
 *
 * Spatie's team scope must be set explicitly because this job runs in
 * a worker context (no HTTP request → no TenantResolver) — without it
 * the role lookup returns zero rows and nobody is notified.
 */
class NotifyResponsableSecteurJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Roles that always get the alert for grave/critique events. */
    private const RESPONSABLE_ROLES = ['dirigeant', 'coordinateur', 'referent_qualite'];

    public function __construct(public readonly Incident $incident) {}

    public function handle(): void
    {
        if ($this->incident->notifie_responsable_at !== null) {
            return;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->incident->structure_id);

        $recipients = User::query()
            ->where('structure_id', $this->incident->structure_id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', self::RESPONSABLE_ROLES))
            ->get();

        if ($recipients->isEmpty()) {
            Log::warning('Aucun responsable destinataire pour incident grave', [
                'incident_id' => $this->incident->id,
                'structure_id' => $this->incident->structure_id,
            ]);

            $this->incident->update(['notifie_responsable_at' => now()]);

            return;
        }

        Notification::send($recipients, new IncidentSeverelyDeclaredNotification($this->incident));

        Log::info('Responsables secteur notifiés', [
            'incident_id' => $this->incident->id,
            'structure_id' => $this->incident->structure_id,
            'recipient_count' => $recipients->count(),
            'gravite' => $this->incident->gravite->value,
        ]);

        $this->incident->update(['notifie_responsable_at' => now()]);
    }
}
