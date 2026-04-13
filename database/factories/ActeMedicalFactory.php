<?php

namespace Database\Factories;

use App\Models\Admission;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActeMedicalFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => fake()->regexify('[A-Za-z0-9]{20}'),
            'libelle' => fake()->regexify('[A-Za-z0-9]{255}'),
            'consultation_id' => Consultation::factory(),
            'admission_id' => Admission::factory(),
            'patient_id' => Patient::factory(),
            'medecin_id' => User::factory(),
            'date_acte' => fake()->dateTime(),
            'description' => fake()->text(),
            'resultat' => fake()->text(),
            'quantite' => fake()->randomNumber(),
            'prix_unitaire' => fake()->randomFloat(2, 0, 99999999.99),
            'prix_total' => fake()->randomFloat(2, 0, 99999999.99),
            'statut' => fake()->randomElement(["planifie","en_cours","termine","annule"]),
        ];
    }
}
