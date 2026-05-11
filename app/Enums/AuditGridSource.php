<?php

namespace App\Enums;

/**
 * Origin of an audit grid template. The three reference standards
 * (HAS, ISO 9001, AFNOR NF X50-056) are seeded per-tenant so each
 * structure can customize their copy without affecting others.
 *
 * Spec: PHASE2_PROGRESS.md M6.1 + IMPLEMENTATION_PLAN line 440 "grid
 * library (HAS, ISO 9001, AFNOR NF X50-056)".
 */
enum AuditGridSource: string
{
    case Has = 'has';
    case Iso9001 = 'iso_9001';
    case AfnorX50056 = 'afnor_x50_056';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Has => 'HAS — Haute Autorité de Santé',
            self::Iso9001 => 'ISO 9001',
            self::AfnorX50056 => 'AFNOR NF X50-056',
            self::Custom => 'Grille personnalisée',
        };
    }

    /**
     * JSON fixture filename under database/seeders/fixtures/, or null
     * for Custom grids (which have no reference fixture).
     */
    public function fixtureFilename(): ?string
    {
        return match ($this) {
            self::Has => 'has-grid.json',
            self::Iso9001 => 'iso9001-grid.json',
            self::AfnorX50056 => 'afnor-x50056-grid.json',
            self::Custom => null,
        };
    }
}
