<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\TypeExamenImagerie;
use App\Models\TypeExamenImagery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamenImagerieFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'numero' => fake()->regexify('[A-Za-z0-9]{20}'),
            'patient_id' => Patient::factory(),
            'consultation_id' => Consultation::factory(),
            'medecin_prescripteur_id' => User::factory(),
            'radiologue_id' => User::factory(),
            'manipulateur_id' => User::factory(),
            'type_examen_id' => TypeExamenImagery::factory(),
            'date_prescription' => fake()->dateTime(),
            'date_examen' => fake()->dateTime(),
            'date_resultat' => fake()->dateTime(),
            'indication_clinique' => fake()->text(),
            'technique' => fake()->text(),
            'resultat' => fake()->text(),
            'conclusion' => fake()->text(),
            'images_path' => '{}',
            'urgent' => fake()->boolean(),
            'statut' => fake()->randomElement(["prescrit","planifie","en_cours","resultat_disponible","valide","annule"]),
            'type_examen_imagerie_id' => TypeExamenImagerie::factory(),
        ];
    }
}
