<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Structure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Welcome email dispatched to the initial dirigeant after a new structure
 * is provisioned. Carries the one-time password-reset link so the dirigeant
 * can set their password and access the platform.
 *
 * Queued (ShouldQueue) — mail delivery must never block the HTTP response
 * or the tenant:provision command. If the queue worker is down the email
 * will be retried automatically; provisioning itself is already committed.
 *
 * Spec: PHASE2_PROGRESS.md C4.
 */
class StructureWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Structure $structure,
        private readonly ?string $passwordResetUrl,
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
        $trialDays = (int) config('billing.trial_days', 30);

        $mail = (new MailMessage)
            ->subject(sprintf('Bienvenue sur QualitéDomicile — %s', $this->structure->name))
            ->greeting(sprintf('Bonjour %s,', $notifiable->first_name))
            ->line(sprintf(
                'Votre structure « %s » a été créée avec succès sur la plateforme QualitéDomicile.',
                $this->structure->name,
            ))
            ->line(sprintf(
                'Vous bénéficiez d\'une période d\'essai de %d jours pour découvrir toutes les fonctionnalités.',
                $trialDays,
            ))
            ->line('Pour accéder à votre espace, commencez par définir votre mot de passe en cliquant sur le bouton ci-dessous.');

        if ($this->passwordResetUrl !== null) {
            $mail->action('Définir mon mot de passe', $this->passwordResetUrl);
        }

        return $mail
            ->line('Ce lien est valable 60 minutes. Au-delà, utilisez « Mot de passe oublié ? » depuis la page de connexion.')
            ->line('Notre équipe est disponible pour vous accompagner dans la prise en main de la plateforme.')
            ->salutation('L\'équipe QualitéDomicile.');
    }
}
