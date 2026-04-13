<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DemandeCongeFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(["annuel","maladie","maternite","paternite","sans_solde","recuperation","formation"]),
            'date_debut' => fake()->date(),
            'date_fin' => fake()->date(),
            'nombre_jours' => fake()->randomNumber(),
            'motif' => fake()->text(),
            'piece_jointe' => fake()->word(),
            'valideur_id' => User::factory(),
            'date_validation' => fake()->dateTime(),
            'commentaire_validation' => fake()->text(),
            'statut' => fake()->randomElement(["en_attente","approuve","refuse","annule"]),
        ];
    }
}
