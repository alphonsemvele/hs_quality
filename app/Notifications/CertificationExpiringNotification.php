<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Certification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when a certification crosses an expiry-alert window
 * (T-90, T-30, T-7, expired). Recipients are the cert owner plus
 * structure-level responsables (responsable formation + coordinateur).
 *
 * The window string drives both the subject line and the body urgency,
 * so a single notification class covers all four windows. The
 * CertificationExpiryAlertJob is the only caller and only fires once
 * per (cert, window) thanks to `last_alert_window`.
 */
class CertificationExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Certification $certification,
        public readonly string $window,
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
        $cert = $this->certification;
        $owner = trim(($cert->user?->first_name ?? '').' '.($cert->user?->last_name ?? ''));
        $expiresOn = $cert->expires_at?->isoFormat('DD/MM/YYYY') ?? '—';

        [$subject, $urgency] = match ($this->window) {
            'T-90' => [
                'Certification à renouveler dans 3 mois',
                'Renouvellement à prévoir.',
            ],
            'T-30' => [
                'Certification à renouveler dans 30 jours',
                'Renouvellement urgent.',
            ],
            'T-7' => [
                'Certification à renouveler dans 7 jours',
                'Renouvellement très urgent.',
            ],
            'expired' => [
                'Certification expirée — renouvellement immédiat',
                'La certification est expirée. Renouvellement immédiat requis.',
            ],
            default => [
                'Alerte certification',
                'Action requise sur une certification.',
            ],
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting(sprintf('Bonjour %s,', $notifiable->first_name ?? ''));

        if ($notifiable->id === $cert->user_id) {
            $mail->line(sprintf('Votre certification %s expire le %s.', $cert->type, $expiresOn));
        } else {
            $mail->line(sprintf(
                'La certification %s de %s expire le %s.',
                $cert->type,
                $owner !== '' ? $owner : '—',
                $expiresOn,
            ));
        }

        return $mail
            ->line($urgency)
            ->line('Pensez à planifier la mise à jour avant l\'échéance.')
            ->salutation('L\'équipe QualitéDomicile.');
    }
}
