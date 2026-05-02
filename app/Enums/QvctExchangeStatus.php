<?php

namespace App\Enums;

/**
 * Lifecycle of a QVCT exchange request.
 *   pending   — awaiting addressee triage
 *   accepted  — addressee acknowledged; meeting not yet scheduled
 *   scheduled — meeting time set; visible in both calendars
 *   closed    — meeting happened (or request was withdrawn / declined)
 */
enum QvctExchangeStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Scheduled = 'scheduled';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Accepted => 'Pris en compte',
            self::Scheduled => 'Planifié',
            self::Closed => 'Clôturé',
        };
    }
}
