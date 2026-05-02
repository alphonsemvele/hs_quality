<?php

namespace App\Enums;

enum PacActionStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'À démarrer',
            self::InProgress => 'En cours',
            self::Done => 'Réalisée',
            self::Cancelled => 'Annulée',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Done || $this === self::Cancelled;
    }
}
