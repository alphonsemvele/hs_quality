<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Three-level conformity rating used by the HAS preparation guide
 * (M6.19). Thresholds are calibrated to the HAS évaluation interne
 * grading scale commonly used for SAAD/SSIAD certification reviews.
 *
 * Conforme       ≥ 70 % of max_points
 * À améliorer    40 % – 69 %
 * Non conforme   < 40 %
 */
enum HASConformityStatus: string
{
    case Conforme = 'conforme';
    case AAmeliorer = 'a_ameliorer';
    case NonConforme = 'non_conforme';

    public static function fromPercentage(float $pct): self
    {
        if ($pct >= 70.0) {
            return self::Conforme;
        }

        if ($pct >= 40.0) {
            return self::AAmeliorer;
        }

        return self::NonConforme;
    }

    public function label(): string
    {
        return match ($this) {
            self::Conforme => 'Conforme',
            self::AAmeliorer => 'À améliorer',
            self::NonConforme => 'Non conforme',
        };
    }

    /** Lower = more urgent. Used to sort items worst-first. */
    public function priority(): int
    {
        return match ($this) {
            self::NonConforme => 1,
            self::AAmeliorer => 2,
            self::Conforme => 3,
        };
    }
}
