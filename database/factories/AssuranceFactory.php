<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AssuranceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => fake()->regexify('[A-Za-z0-9]{20}'),
            'nom' => fake()->regexify('[A-Za-z0-9]{100}'),
            'type' => fake()->randomElement(["publique","privee","mutuelle"]),
            'adresse' => fake()->text(),
            'telephone' => fake()->regexify('[A-Za-z0-9]{20}'),
            'email' => fake()->safeEmail(),
            'taux_couverture' => fake()->randomFloat(2, 0, 999.99),
            'plafond_annuel' => fake()->randomFloat(2, 0, 9999999999.99),
            'delai_paiement' => fake()->randomNumber(),
            'contact_nom' => fake()->regexify('[A-Za-z0-9]{100}'),
            'contact_telephone' => fake()->regexify('[A-Za-z0-9]{20}'),
            'actif' => fake()->boolean(),
        ];
    }
}
