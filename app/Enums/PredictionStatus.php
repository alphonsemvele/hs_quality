<?php

declare(strict_types=1);

namespace App\Enums;

enum PredictionStatus: string
{
    /** Job created but not yet sent to the ML service (circuit open or queue backlog). */
    case EnAttente = 'en_attente';

    /** ML service has accepted the job and is processing. */
    case EnTraitement = 'en_traitement';

    /** ML service returned a result; stored in `result` column. */
    case Termine = 'termine';

    /** ML service returned an error or the job exhausted its retries. */
    case Echoue = 'echoue';

    public function isTerminal(): bool
    {
        return $this === self::Termine || $this === self::Echoue;
    }

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En calcul',
            self::EnTraitement => 'En traitement',
            self::Termine => 'Terminé',
            self::Echoue => 'Échoué',
        };
    }
}
