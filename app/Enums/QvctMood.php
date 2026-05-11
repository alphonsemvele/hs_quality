<?php

namespace App\Enums;

/**
 * Self-reported mood on a QVCT journal entry. 5-point scale matching the
 * baromètre's 1-5 question scale so trend visualisations can plot
 * mood + baromètre on the same axis.
 *
 * Spec: PHASE2_PROGRESS.md M3.22, CDC §M3 line "Optional emotional journal".
 */
enum QvctMood: string
{
    case TresNegatif = 'tres_negatif';
    case Negatif = 'negatif';
    case Neutre = 'neutre';
    case Positif = 'positif';
    case TresPositif = 'tres_positif';

    public function label(): string
    {
        return match ($this) {
            self::TresNegatif => 'Très négatif',
            self::Negatif => 'Négatif',
            self::Neutre => 'Neutre',
            self::Positif => 'Positif',
            self::TresPositif => 'Très positif',
        };
    }

    /** Numeric score 1-5 for plotting alongside baromètre means. */
    public function score(): int
    {
        return match ($this) {
            self::TresNegatif => 1,
            self::Negatif => 2,
            self::Neutre => 3,
            self::Positif => 4,
            self::TresPositif => 5,
        };
    }
}
