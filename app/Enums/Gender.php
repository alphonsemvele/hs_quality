<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'm';
    case Female = 'f';
    case Unspecified = 'u';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Homme',
            self::Female => 'Femme',
            self::Unspecified => 'Non spécifié',
        };
    }
}
