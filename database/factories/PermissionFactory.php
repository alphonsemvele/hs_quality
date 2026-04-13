<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nom' => fake()->regexify('[A-Za-z0-9]{100}'),
            'description' => fake()->text(),
            'module' => fake()->regexify('[A-Za-z0-9]{50}'),
        ];
    }
}
