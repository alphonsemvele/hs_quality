<?php

namespace Database\Factories;

use App\Models\Delivrance;
use App\Models\Medicament;
use Illuminate\Database\Eloquent\Factories\Factory;

class LigneDelivranceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'delivrance_id' => Delivrance::factory(),
            'medicament_id' => Medicament::factory(),
            'quantite' => fake()->randomNumber(),
            'prix_unitaire' => fake()->randomFloat(2, 0, 99999999.99),
            'prix_total' => fake()->randomFloat(2, 0, 99999999.99),
            'lot' => fake()->regexify('[A-Za-z0-9]{50}'),
            'date_expiration' => fake()->date(),
        ];
    }
}
