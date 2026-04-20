<?php

namespace App\Enums;

enum InterventionStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Missed = 'missed';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planifiée',
            self::InProgress => 'En cours',
            self::Completed => 'Réalisée',
            self::Cancelled => 'Annulée',
            self::Missed => 'Manquée',
        };
    }

    /** True for terminal states — no further lifecycle transitions allowed. */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Cancelled, self::Missed => true,
            default => false,
        };
    }
}
