<?php

namespace Database\Factories;

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\TypeAnalysis;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnalyseLaboratoireFactory extends Factory
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
            'technicien_id' => User::factory(),
            'biologiste_id' => User::factory(),
            'type_analyse_id' => TypeAnalysis::factory(),
            'date_prescription' => fake()->dateTime(),
            'date_prelevement' => fake()->dateTime(),
            'date_resultat' => fake()->dateTime(),
            'resultat' => '{}',
            'interpretation' => fake()->text(),
            'conclusion' => fake()->text(),
            'commentaire_medecin' => fake()->text(),
            'urgent' => fake()->boolean(),
            'statut' => fake()->randomElement(["prescrit","preleve","en_cours","resultat_disponible","valide","annule"]),
        ];
    }
}
