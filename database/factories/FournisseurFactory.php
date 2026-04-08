<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FournisseurFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => fake()->regexify('[A-Za-z0-9]{20}'),
            'nom' => fake()->regexify('[A-Za-z0-9]{150}'),
            'type' => fake()->randomElement(["laboratoire","grossiste","importateur"]),
            'adresse' => fake()->text(),
            'ville' => fake()->regexify('[A-Za-z0-9]{50}'),
            'pays' => fake()->regexify('[A-Za-z0-9]{50}'),
            'telephone' => fake()->regexify('[A-Za-z0-9]{20}'),
            'email' => fake()->safeEmail(),
            'site_web' => fake()->regexify('[A-Za-z0-9]{200}'),
            'contact_nom' => fake()->regexify('[A-Za-z0-9]{100}'),
            'contact_telephone' => fake()->regexify('[A-Za-z0-9]{20}'),
            'delai_livraison_jours' => fake()->randomNumber(),
            'conditions_paiement' => fake()->text(),
            'actif' => fake()->boolean(),
        ];
    }
}
