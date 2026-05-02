<?php

namespace App\Enums;

/**
 * Scoring scale used by an audit grid item. Covers the main shapes
 * found in HAS / ISO 9001 / AFNOR grids: yes-no, 1-5 maturity, and
 * percentage-coverage scoring.
 */
enum AuditItemScale: string
{
    case Binary = 'binary';
    case OneToFive = '1_to_5';
    case Percentage = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::Binary => 'Oui / Non',
            self::OneToFive => 'Échelle 1-5',
            self::Percentage => 'Pourcentage',
        };
    }

    /** Maximum score a single response can hit on this scale. */
    public function maxScore(): float
    {
        return match ($this) {
            self::Binary => 1.0,
            self::OneToFive => 5.0,
            self::Percentage => 100.0,
        };
    }
}
