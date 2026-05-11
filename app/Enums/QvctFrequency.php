<?php

namespace App\Enums;

/**
 * Cadence at which a QVCT questionnaire is re-launched as new campaigns.
 * Set on the questionnaire template; the actual campaign carries its own
 * start/end dates so a structure can shift a launch by a few days without
 * losing the cadence semantics.
 *
 * Spec: CDC §M3 line "Parametrable frequency: weekly / monthly / quarterly".
 */
enum QvctFrequency: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Hebdomadaire',
            self::Monthly => 'Mensuelle',
            self::Quarterly => 'Trimestrielle',
        };
    }

    /** Days in one cadence period — used by the campaign-scheduler. */
    public function periodDays(): int
    {
        return match ($this) {
            self::Weekly => 7,
            self::Monthly => 30,
            self::Quarterly => 90,
        };
    }
}
