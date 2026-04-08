<?php

namespace Database\Factories;

use App\Models\Admission;
use App\Models\Assurance;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class FactureFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'numero' => fake()->regexify('[A-Za-z0-9]{20}'),
            'patient_id' => Patient::factory(),
            'admission_id' => Admission::factory(),
            'assurance_id' => Assurance::factory(),
            'date_facture' => fake()->date(),
            'date_echeance' => fake()->date(),
            'montant_brut' => fake()->randomFloat(2, 0, 9999999999.99),
            'remise' => fake()->randomFloat(2, 0, 9999999999.99),
            'montant_assurance' => fake()->randomFloat(2, 0, 9999999999.99),
            'montant_patient' => fake()->randomFloat(2, 0, 9999999999.99),
            'montant_total' => fake()->randomFloat(2, 0, 9999999999.99),
            'montant_paye' => fake()->randomFloat(2, 0, 9999999999.99),
            'tva' => fake()->randomFloat(2, 0, 9999999999.99),
            'notes' => fake()->text(),
            'statut' => fake()->randomElement(["brouillon","emise","partiellement_payee","payee","annulee"]),
        ];
    }
}
