<?php

namespace App\Enums;

enum StructureTier: string
{
    case Essential = 'essential';
    case Pro = 'pro';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Essential => 'Essentiel',
            self::Pro => 'Pro',
            self::Premium => 'Premium',
        };
    }

    public function monthlyPricePerUser(): int
    {
        return match ($this) {
            self::Essential => 8,
            self::Pro => 15,
            self::Premium => 25,
        };
    }
}
