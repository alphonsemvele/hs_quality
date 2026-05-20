<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Whitelist of field_keys that support tenant-managed custom options.
 *
 * Only user-facing classification fields belong here. Never add lifecycle
 * statuses (statut_*), regulatory severity levels (gravite_incident),
 * structural types (type_structure), or billing tiers — their values carry
 * legal / ARS / system meaning that must not vary per tenant.
 */
enum CustomOptionField: string
{
    case Gir = 'gir';
    case CategorieIncident = 'categorie_incident';
    case ReferentielQualite = 'referentiel_qualite';
}
