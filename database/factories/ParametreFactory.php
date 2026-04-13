<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ParametreFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'cle' => fake()->regexify('[A-Za-z0-9]{100}'),
            'valeur' => fake()->text(),
            'type' => fake()->randomElement(["string","integer","boolean","json","date"]),
            'groupe' => fake()->regexify('[A-Za-z0-9]{50}'),
            'description' => fake()->text(),
            'modifiable' => fake()->boolean(),
        ];
    }
}
