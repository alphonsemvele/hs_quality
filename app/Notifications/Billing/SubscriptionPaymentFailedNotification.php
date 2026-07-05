<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\Structure;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when Stripe reports a failed invoice payment for a structure's
 * subscription. Goes to every dirigeant of the structure so the fix
 * (update card / re-issue payment) doesn't sit waiting on one inbox.
 *
 * Stripe will retry the payment a few times before moving the
 * subscription to past_due / unpaid; we surface that to the customer
 * proactively rather than waiting for the grace period to expire.
 */
class SubscriptionPaymentFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Structure $structure,
        public readonly ?int $amountCents,
        public readonly ?string $currency,
        public readonly ?string $hostedInvoiceUrl,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amountLabel = $this->amountCents !== null
            ? sprintf('%s %s', number_format($this->amountCents / 100, 2, ',', ' '), strtoupper($this->currency ?? 'EUR'))
            : 'le montant de la facture';

        $mail = (new MailMessage)
            ->error()
            ->subject('Échec du paiement de votre abonnement QualitéDomicile')
            ->greeting('Bonjour '.($notifiable->first_name ?? '').',')
            ->line(sprintf(
                'Le prélèvement de %s pour l\'abonnement de la structure **%s** a échoué.',
                $amountLabel,
                $this->structure->name,
            ))
            ->line('Stripe va effectuer plusieurs nouvelles tentatives automatiques dans les prochains jours. Pour éviter une suspension du service, vérifiez votre moyen de paiement dès que possible.')
            ->action('Mettre à jour mon moyen de paiement', url('/billing'));

        if (is_string($this->hostedInvoiceUrl) && $this->hostedInvoiceUrl !== '') {
            $mail->line('Vous pouvez également régler directement la facture en ligne :')
                ->line($this->hostedInvoiceUrl);
        }

        return $mail
            ->line('Sans régularisation sous 14 jours, l\'accès aux fonctionnalités payantes pourra être temporairement suspendu.')
            ->salutation('L\'équipe QualitéDomicile.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'billing.payment.failed',
            'structure_id' => $this->structure->id,
            'amount_cents' => $this->amountCents,
            'currency' => $this->currency,
        ];
    }
}
