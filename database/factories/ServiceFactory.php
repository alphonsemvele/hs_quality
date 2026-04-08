<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => fake()->regexify('[A-Za-z0-9]{20}'),
            'nom' => fake()->regexify('[A-Za-z0-9]{100}'),
            'description' => fake()->text(),
            'chef_service_id' => User::factory(),
            'etage' => fake()->regexify('[A-Za-z0-9]{10}'),
            'batiment' => fake()->regexify('[A-Za-z0-9]{50}'),
            'telephone' => fake()->regexify('[A-Za-z0-9]{20}'),
            'email' => fake()->safeEmail(),
            'capacite_lits' => fake()->randomNumber(),
            'actif' => fake()->boolean(),
        ];
    }
}
