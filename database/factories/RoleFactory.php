<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nom' => fake()->regexify('[A-Za-z0-9]{50}'),
            'description' => fake()->text(),
            'couleur' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
