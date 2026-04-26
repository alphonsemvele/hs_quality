<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Structure;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when a dirigeant / coordinateur invites a new team member to their
 * structure. Carries the password-reset link the invitee uses to set
 * their initial password.
 *
 * Routed via the default mail channel; will degrade gracefully (logged
 * but not thrown) if mail config is missing — UserInvitationService
 * also returns the invitation URL so the inviter can hand it off
 * directly during the gap.
 */
class UserInvitedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Structure $structure,
        private readonly User $invitedBy,
        private readonly ?string $invitationUrl,
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
        $inviter = trim($this->invitedBy->first_name.' '.$this->invitedBy->last_name);

        $mail = (new MailMessage)
            ->subject(sprintf('Invitation à rejoindre %s sur QualitéDomicile', $this->structure->name))
            ->greeting(sprintf('Bonjour %s,', $notifiable->first_name))
            ->line(sprintf(
                '%s vous invite à rejoindre la structure « %s » sur la plateforme QualitéDomicile.',
                $inviter,
                $this->structure->name,
            ))
            ->line('Pour activer votre compte, cliquez sur le bouton ci-dessous afin de définir votre mot de passe.');

        if ($this->invitationUrl !== null) {
            $mail->action('Définir mon mot de passe', $this->invitationUrl);
        }

        return $mail
            ->line('Ce lien est valable 60 minutes. Au-delà, demandez un nouveau lien depuis la page de connexion via « Mot de passe oublié ? ».')
            ->salutation('L\'équipe QualitéDomicile.');
    }
}
