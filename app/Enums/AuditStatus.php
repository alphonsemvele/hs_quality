<?php

namespace App\Enums;

enum AuditStatus: string
{
    case Planifie = 'planifie';
    case EnCours = 'en_cours';
    case Termine = 'termine';
    case Annule = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::Planifie => 'Planifié',
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
