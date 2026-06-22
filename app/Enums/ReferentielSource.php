<?php

namespace App\Enums;

/**
 * Source d'une exigence individuelle dans la grille unifiée SAP. Distinct
 * de AuditGridSource (qui désigne l'origine d'une grille entière) : une
 * exigence peut être issue de plusieurs référentiels à la fois — c'est
 * pourquoi AuditGridItem.sources est un tableau de ces valeurs.
 */
enum ReferentielSource: string
{
    case Has = 'HAS';
    case Afnor = 'AFNOR';
    case CapHandeo = 'CAP_HANDEO';

    public function label(): string
    {
        return match ($this) {
            self::Has => 'HAS',
            self::Afnor => 'AFNOR',
            self::CapHandeo => "Cap'Handéo",
        };
    }
}
