<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TourneeFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'date' => fake()->date(),
            'soignant_id' => User::factory(),
            'vehicule' => fake()->regexify('[A-Za-z0-9]{50}'),
            'heure_debut_prevue' => fake()->time(),
            'heure_fin_prevue' => fake()->time(),
            'heure_debut_effective' => fake()->time(),
            'heure_fin_effective' => fake()->time(),
            'kilometres' => fake()->randomFloat(2, 0, 999999.99),
            'notes' => fake()->text(),
            'statut' => fake()->randomElement(["planifiee","en_cours","terminee","annulee"]),
        ];
    }
}
