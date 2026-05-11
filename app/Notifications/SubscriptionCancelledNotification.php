<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Structure;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Compliance notice dispatched to the initial dirigeant when a structure
 * cancels its subscription.
 *
 * GDPR / données de santé obligation: the user must be explicitly informed
 * that their data is retained for 30 days after cancellation date, then
 * purged from the platform unless they resubscribe.
 *
 * Queued (ShouldQueue) — mail must not block the cancel HTTP response.
 *
 * Spec: PHASE2_PROGRESS.md C6.
 */
class SubscriptionCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Structure $structure,
        private readonly \DateTimeInterface $endsAt,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $retentionDeadline = Carbon::parse($this->endsAt)->addDays(30)->format('d/m/Y');

        return (new MailMessage)
            ->subject(sprintf('Résiliation de votre abonnement QualitéDomicile — %s', $this->structure->name))
            ->greeting(sprintf('Bonjour %s,', $notifiable->first_name))
            ->line(sprintf(
                'Nous avons bien enregistré la résiliation de l\'abonnement de la structure « %s ».',
                $this->structure->name,
            ))
            ->line(sprintf(
                'Votre accès reste actif jusqu\'au %s (fin de la période en cours).',
                Carbon::parse($this->endsAt)->format('d/m/Y'),
            ))
            ->line(sprintf(
                'Conformément à la réglementation sur la protection des données de santé, '
                .'vos données seront conservées sur nos serveurs jusqu\'au **%s**, '
                .'puis définitivement supprimées.',
                $retentionDeadline,
            ))
            ->line('Pour réactiver votre abonnement avant cette date, connectez-vous et choisissez un plan dans les paramètres de votre structure.')
            ->action('Réactiver mon abonnement', config('app.url').'/settings/billing')
            ->line('Pour toute question, contactez notre support à support@qualitedomicile.fr.')
            ->salutation('L\'équipe QualitéDomicile.');
    }
}
