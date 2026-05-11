<?php

namespace App\Enums;

/**
 * Lifecycle of a QVCT action plan.
 *   draft     — RH building it; items can be added/edited freely
 *   published — visible to coordinateurs and intervenants; items
 *               can no longer be added/removed (only status + impact
 *               can be updated)
 *   closed    — period over; impact measurement frozen
 */
enum QvctActionPlanStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Published => 'Publié',
            self::Closed => 'Clôturé',
        };
    }
}
