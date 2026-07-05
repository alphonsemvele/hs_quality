<?php

declare(strict_types=1);

namespace App\Notifications\Incidents;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Internal notification fired when a grave/critique incident is declared
 * — sent to the structure's responsable surface (dirigeant, coordinateur,
 * référent qualité) so they're alerted within minutes of the event,
 * regardless of whether they have the web app open. Database channel
 * also fires so the in-app notification center reflects it.
 */
class IncidentSeverelyDeclaredNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Incident $incident) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $incident = $this->incident;
        $occurredAt = $incident->occurred_at?->translatedFormat('d/m/Y à H:i') ?? '—';
        $gravite = $incident->gravite->value;

        return (new MailMessage)
            ->error()
            ->subject(sprintf('[%s] Incident %s déclaré dans votre structure', strtoupper($gravite), $incident->categorie->value))
            ->greeting('Bonjour '.($notifiable->first_name ?? '').',')
            ->line(sprintf(
                'Un incident de gravité **%s** vient d\'être déclaré dans votre structure et requiert une prise en charge immédiate.',
                $gravite,
            ))
            ->line('Catégorie : '.$incident->categorie->value)
            ->line('Date des faits : '.$occurredAt)
            ->action('Ouvrir l\'incident', url('/incidents/'.$incident->id))
            ->line('Le détail complet de l\'incident est disponible sur la plateforme. Un suivi 5-pourquoi et un plan d\'actions correctives doivent être engagés sans délai.')
            ->salutation('L\'équipe QualitéDomicile.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'incident.declared.severely',
            'incident_id' => $this->incident->id,
            'gravite' => $this->incident->gravite->value,
            'categorie' => $this->incident->categorie->value,
            'occurred_at' => $this->incident->occurred_at?->toIso8601String(),
        ];
    }
}
