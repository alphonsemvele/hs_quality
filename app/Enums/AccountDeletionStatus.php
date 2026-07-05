<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountDeletionStatus: string
{
    case Pending = 'pending';
    case Cancelled = 'cancelled';
    case Processed = 'processed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En cours (délai de 30 jours)',
            self::Cancelled => 'Annulée',
            self::Processed => 'Effacement effectué',
            self::Failed => 'Échec',
        };
    }
}
