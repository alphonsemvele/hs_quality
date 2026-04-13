<?php

namespace Database\Factories;

use App\Models\Medicament;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MouvementStockFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'medicament_id' => Medicament::factory(),
            'type' => fake()->randomElement(["entree","sortie","ajustement","perte","peremption"]),
            'quantite' => fake()->numberBetween(-10000, 10000),
            'stock_avant' => fake()->randomNumber(),
            'stock_apres' => fake()->randomNumber(),
            'motif' => fake()->regexify('[A-Za-z0-9]{255}'),
            'reference' => fake()->regexify('[A-Za-z0-9]{50}'),
            'lot' => fake()->regexify('[A-Za-z0-9]{50}'),
            'date_expiration' => fake()->date(),
            'prix_unitaire' => fake()->randomFloat(2, 0, 99999999.99),
            'user_id' => User::factory(),
        ];
    }
}
