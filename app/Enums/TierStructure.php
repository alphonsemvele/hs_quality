<?php

namespace App\Enums;

enum TierStructure: string
{
    case Essentiel = 'essentiel';
    case Pro = 'pro';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Essentiel => 'Essentiel',
            self::Pro => 'Pro',
            self::Premium => 'Premium',
        };
    }

    public function monthlyPricePerUser(): int
    {
        return match ($this) {
            self::Essentiel => 8,
            self::Pro => 15,
            self::Premium => 25,
        };
    }
}
