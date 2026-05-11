<?php

namespace App\Enums;

/**
 * Plan d'Amélioration Continue (PAC) lifecycle.
 *   draft   — RH/référent qualité building it (auto-generated from
 *             audit gaps OR manually authored)
 *   active  — published; actions assignable / trackable
 *   closed  — period over; impact recorded, no further mutations
 */
enum PacStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Active => 'Actif',
            self::Closed => 'Clôturé',
        };
    }
}
