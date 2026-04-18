<?php

namespace App\Enums;

enum StatutStructure: string
{
    case Active = 'active';
    case Suspendue = 'suspendue';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspendue => 'Suspendue',
        };
    }
}
