<?php

namespace App\Enums;

enum ActionStatus: string
{
    case Planifiee = 'planifiee';
    case EnCours = 'en_cours';
    case Realisee = 'realisee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::Planifiee => 'Planifiée',
            self::EnCours => 'En cours',
            self::Realisee => 'Réalisée',
            self::Annulee => 'Annulée',
        };
    }

    public function isDone(): bool
    {
        return $this === self::Realisee;
    }

    public function isOpen(): bool
    {
        return $this === self::Planifiee || $this === self::EnCours;
    }
}
