<?php

namespace App\Enums;

/**
 * The standards an audit can be conducted against. Sector-specific to
 * French SAAD/SSIAD/SPASAD/ESAD/CCAS structures (HAS, AFNOR NF X50-056,
 * ISO 9001) plus an "interne" option for structure-defined evaluations.
 */
enum Referentiel: string
{
    case Has = 'has';
    case Afnor = 'afnor';
    case Iso9001 = 'iso_9001';
    case Interne = 'interne';

    public function label(): string
    {
        return match ($this) {
            self::Has => 'HAS',
            self::Afnor => 'AFNOR NF X50-056',
            self::Iso9001 => 'ISO 9001',
            self::Interne => 'Référentiel interne',
        };
    }
}
