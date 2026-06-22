<?php

namespace App\Enums;

/**
 * Scoring scale used by an audit grid item. Covers the main shapes
 * found in HAS / ISO 9001 / AFNOR grids: yes-no, 1-5 maturity,
 * percentage-coverage scoring, and the HAS A/B/C/D/NA cotation used
 * by the unified SAP grid (HAS + AFNOR + Cap'Handéo).
 */
enum AuditItemScale: string
{
    case Binary = 'binary';
    case OneToFive = '1_to_5';
    case Percentage = 'percentage';
    case HasCotation = 'has_cotation';

    public function label(): string
    {
        return match ($this) {
            self::Binary => 'Oui / Non',
            self::OneToFive => 'Échelle 1-5',
            self::Percentage => 'Pourcentage',
            self::HasCotation => 'Cotation HAS (A/B/C/D/NA)',
        };
    }

    /** Maximum score a single response can hit on this scale. */
    public function maxScore(): float
    {
        return match ($this) {
            self::Binary => 1.0,
            self::OneToFive => 5.0,
            self::Percentage => 100.0,
            self::HasCotation => 4.0,
        };
    }

    /**
     * Map a HAS cotation code to its numeric score:
     *   A → 4, B → 3, C → 2, D → 1, NA → null (excluded from scoring).
     *
     * Returns null for invalid codes or for any scale other than HasCotation.
     */
    public static function scoreForCotation(?string $code): ?float
    {
        return match ($code) {
            'A' => 4.0,
            'B' => 3.0,
            'C' => 2.0,
            'D' => 1.0,
            default => null,
        };
    }

    /** Valid HAS cotation codes (including the explicit "Non applicable"). */
    public static function cotationCodes(): array
    {
        return ['A', 'B', 'C', 'D', 'NA'];
    }
}
