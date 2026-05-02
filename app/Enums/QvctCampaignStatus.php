<?php

namespace App\Enums;

/**
 * Lifecycle of a QVCT campaign instance.
 *
 *   draft     — questionnaire being prepared; no responses accepted
 *   active    — within the launch window; intervenants can submit responses
 *   closed    — past end_date or manually closed; weak-signal detection has run
 *   archived  — superseded by a newer cadence; kept read-only for trends
 */
enum QvctCampaignStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Active => 'En cours',
            self::Closed => 'Clôturée',
            self::Archived => 'Archivée',
        };
    }

    public function acceptsResponses(): bool
    {
        return $this === self::Active;
    }
}
