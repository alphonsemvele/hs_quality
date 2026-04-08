<?php

namespace Database\Factories;

use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommandeFournisseurFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'numero' => fake()->regexify('[A-Za-z0-9]{20}'),
            'fournisseur_id' => Fournisseur::factory(),
            'user_id' => User::factory(),
            'date_commande' => fake()->date(),
            'date_livraison_prevue' => fake()->date(),
            'date_livraison_effective' => fake()->date(),
            'montant_total' => fake()->randomFloat(2, 0, 9999999999.99),
            'notes' => fake()->text(),
            'statut' => fake()->randomElement(["brouillon","envoyee","confirmee","livree_partiellement","livree","annulee"]),
        ];
    }
}
