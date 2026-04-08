<?php

namespace Database\Factories;

use App\Models\CommandeFournisseur;
use App\Models\Medicament;
use Illuminate\Database\Eloquent\Factories\Factory;

class LigneCommandeFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'commande_id' => CommandeFournisseur::factory(),
            'medicament_id' => Medicament::factory(),
            'quantite_commandee' => fake()->randomNumber(),
            'quantite_recue' => fake()->randomNumber(),
            'prix_unitaire' => fake()->randomFloat(2, 0, 99999999.99),
            'prix_total' => fake()->randomFloat(2, 0, 99999999.99),
        ];
    }
}
