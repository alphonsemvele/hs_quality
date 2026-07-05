<?php

declare(strict_types=1);

namespace App\Notifications\Gdpr;

use App\Models\DataExportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DataExportReadyNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly DataExportRequest $request) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiresAt = $this->request->expires_at?->translatedFormat('d/m/Y à H:i') ?? '—';

        return (new MailMessage)
            ->subject('Votre export de données est prêt')
            ->greeting('Bonjour '.($notifiable->first_name ?? '').',')
            ->line('Votre demande d\'export de données personnelles (RGPD article 15 / 20) a bien été traitée.')
            ->line(sprintf('Le lien de téléchargement est disponible sur votre tableau de bord jusqu\'au %s.', $expiresAt))
            ->action('Accéder à mon export', url('/dashboard/profile/gdpr'))
            ->line('Pour des raisons de sécurité, le lien de téléchargement requiert une authentification à votre compte.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'gdpr.export.ready',
            'request_id' => $this->request->id,
            'expires_at' => $this->request->expires_at?->toIso8601String(),
        ];
    }
}
