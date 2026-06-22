<?php

namespace App\Enums;

/**
 * Niveau d'exigence dans la grille unifiée SAP (HAS / AFNOR / Cap'Handéo).
 *
 * - Standard (S) : niveau attendu de qualité.
 * - Imperatif (I) : exigence HAS dont la non-satisfaction implique un plan
 *   d'action immédiat. 18 critères impératifs dans le référentiel HAS national.
 * - Supra : exigence Cap'Handéo allant au-delà du cadre légal (badge "+").
 */
enum ExigenceLevel: string
{
    case Standard = 'S';
    case Imperatif = 'I';
    case Supra = '+';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::Imperatif => 'Impératif',
            self::Supra => 'Supra-réglementaire',
        };
    }

    public function code(): string
    {
        return $this->value;
    }

    /**
     * Tailwind tone class used by badges in the UI. Critical (Imperatif)
     * uses danger tones, Supra uses brand tones, Standard stays neutral.
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::Standard => 'bg-ink-100 text-ink-700 dark:bg-ink-800 dark:text-ink-200',
            self::Imperatif => 'bg-danger-100 text-danger-700 dark:bg-danger-900/40 dark:text-danger-200',
            self::Supra => 'bg-brand-100 text-brand-700 dark:bg-brand-900/40 dark:text-brand-200',
        };
    }
}
