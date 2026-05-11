<?php

namespace App\Enums;

enum AuditRunStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Finalised = 'finalised';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::InProgress => 'En cours',
            self::Finalised => 'Finalisée',
        };
    }
}
