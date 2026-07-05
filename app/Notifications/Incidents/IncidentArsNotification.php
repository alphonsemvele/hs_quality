<?php

declare(strict_types=1);

namespace App\Notifications\Incidents;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Formal ARS (Agence Régionale de Santé) notification — CDC §6.3 requires
 * a structured external declaration within 24h of any grave/critique
 * incident. Mail-only by design: we don't store the body of the message
 * in the database, only the timestamp on the Incident itself.
 *
 * The recipient is set by the dispatching job via Notification::route
 * (no User model on the receiving end), so this class doesn't care
 * about $notifiable's identity — the From line and content are
 * structure-driven.
 */
class IncidentArsNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Incident $incident) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $incident = $this->incident;
        $structure = $incident->structure;
        $occurredAt = $incident->occurred_at?->translatedFormat('d/m/Y à H:i') ?? '—';

        $mail = (new MailMessage)
            ->subject(sprintf(
                '[ARS - Déclaration d\'événement indésirable] %s — %s',
                $structure?->name ?? '—',
                strtoupper($incident->gravite->value),
            ))
            ->line('Conformément à l\'article L. 1413-14 du Code de la santé publique, nous portons à la connaissance de l\'Agence Régionale de Santé l\'événement indésirable suivant :')
            ->line('**Structure déclarante**')
            ->line('Nom : '.($structure?->name ?? '—'))
            ->line('SIRET : '.($structure?->siret ?? '—'))
            ->line('Type : '.($structure?->type?->value ?? '—'))
            ->line('**Événement**')
            ->line('Identifiant interne : '.$incident->id)
            ->line('Catégorie : '.$incident->categorie->value)
            ->line('Gravité : '.$incident->gravite->value)
            ->line('Date des faits : '.$occurredAt)
            ->line('Une prise en charge immédiate a été engagée par la structure. Un retour d\'expérience structuré sera joint à la clôture du dossier.')
            ->salutation('Cordialement,');

        $cc = config('incidents.ars.cc');
        if (is_string($cc) && $cc !== '') {
            $mail->cc($cc);
        }

        return $mail;
    }
}
