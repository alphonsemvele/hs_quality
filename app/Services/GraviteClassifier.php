<?php

namespace App\Services;

use App\Enums\CategorieIncident;
use App\Enums\GraviteIncident;

/**
 * Pure function — no DB, no side effects.
 * Maps incident inputs to a gravity level per CDC §6.2 classification rules.
 */
class GraviteClassifier
{
    /**
     * @param  bool  $avecDeces  Death occurred
     * @param  bool  $avecHospitalisation  Required hospitalisation
     * @param  bool  $avecBlessurePhysique  Physical injury without hospitalisation
     */
    public static function classify(
        CategorieIncident $categorie,
        bool $avecDeces = false,
        bool $avecHospitalisation = false,
        bool $avecBlessurePhysique = false,
    ): GraviteIncident {
        // Death always escalates to critique regardless of category.
        if ($avecDeces) {
            return GraviteIncident::Critique;
        }

        // These categories carry an inherently critical risk per CDC §6.2.
        if (in_array($categorie, [
            CategorieIncident::MaltraitanceSuspecte,
            CategorieIncident::SituationDanger,
        ], true)) {
            return GraviteIncident::Critique;
        }

        // Hospitalisation or medication error escalates to grave.
        if ($avecHospitalisation || $categorie === CategorieIncident::ErreurMedicamenteuse) {
            return GraviteIncident::Grave;
        }

        // Physical injury or aggression without hospitalisation is significatif.
        if ($avecBlessurePhysique || $categorie === CategorieIncident::Agression) {
            return GraviteIncident::Significatif;
        }

        return GraviteIncident::Mineur;
    }
}
