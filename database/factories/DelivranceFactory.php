<?php

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DelivranceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'numero' => fake()->regexify('[A-Za-z0-9]{20}'),
            'prescription_id' => Prescription::factory(),
            'pharmacien_id' => User::factory(),
            'date_delivrance' => fake()->dateTime(),
            'montant_total' => fake()->randomFloat(2, 0, 99999999.99),
            'montant_paye' => fake()->randomFloat(2, 0, 99999999.99),
            'mode_paiement' => fake()->randomElement(["especes","carte","mobile_money","assurance","credit"]),
            'notes' => fake()->text(),
            'statut' => fake()->randomElement(["en_cours","terminee","annulee"]),
        ];
    }
}
