<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\QvctWeakSignal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when WeakSignalDetector emits a psychosocial-risk weak signal
 * after a campaign closes. Recipients are the structure's RH +
 * dirigeant + référent qualité — anyone who can act on a signal
 * (acknowledge, plan a follow-up exchange, open a PAC).
 *
 * The signal payload itself is structure-tagged but anonymous:
 * `team_tag` (e.g. "Paris Centre"), `signal_type`, severity 1-5, and
 * the aggregated `mean_score`. No respondent identity ever surfaces —
 * QVCT's anonymity invariant must hold even in alert mail.
 */
class WeakSignalDetectedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly QvctWeakSignal $signal) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $signal = $this->signal;
        $teamLabel = $signal->team_tag ?? 'structure entière';
        $signalKind = $signal->signal_type->value;
        $severity = $signal->severity;
        $meanScore = $signal->details['mean_score'] ?? null;

        $urgency = match (true) {
            $severity >= 4 => 'Action prioritaire requise.',
            $severity === 3 => 'Action recommandée à court terme.',
            default => 'À surveiller.',
        };

        $mail = (new MailMessage)
            ->subject(sprintf('Signal QVCT à traiter — équipe %s', $teamLabel))
            ->greeting(sprintf('Bonjour %s,', $notifiable->first_name ?? ''))
            ->line(sprintf(
                'Un signal QVCT de type « %s » a été détecté pour l\'équipe « %s » (sévérité %d/5).',
                $signalKind,
                $teamLabel,
                $severity,
            ));

        if ($meanScore !== null) {
            $mail->line(sprintf('Score moyen agrégé : %.2f.', (float) $meanScore));
        }

        return $mail
            ->line($urgency)
            ->line('Aucune information individuelle n\'est partagée — l\'anonymat des répondants est préservé.')
            ->line('Connectez-vous à QualitéDomicile pour consulter les détails et planifier un suivi.')
            ->salutation('L\'équipe QualitéDomicile.');
    }
}
