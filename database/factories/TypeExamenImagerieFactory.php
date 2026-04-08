<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TypeExamenImagerieFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => fake()->regexify('[A-Za-z0-9]{20}'),
            'nom' => fake()->regexify('[A-Za-z0-9]{150}'),
            'modalite' => fake()->randomElement(["radiographie","echographie","scanner","irm","mammographie","autre"]),
            'description' => fake()->text(),
            'preparation_patient' => fake()->text(),
            'duree_minutes' => fake()->randomNumber(),
            'prix' => fake()->randomFloat(2, 0, 99999999.99),
            'actif' => fake()->boolean(),
        ];
    }
}
