<?php

namespace App\Enums;

enum PlanAmeliorationStatus: string
{
    case Ouvert = 'ouvert';
    case EnCours = 'en_cours';
    case Termine = 'termine';
    case Annule = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::Ouvert => 'Ouvert',
            self::EnCours => 'En cours',
            self::Termine => 'Terminé',
            self::Annule => 'Annulé',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Termine || $this === self::Annule;
    }
}
