<?php

namespace App\Enums;

enum CarePlanStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Active => 'Actif',
            self::Archived => 'Archivé',
        };
    }

    public function isEditable(): bool
    {
        return $this !== self::Archived;
    }
}
