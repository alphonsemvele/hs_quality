<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(["create","update","delete","view","login","logout","export"]),
            'model' => fake()->regexify('[A-Za-z0-9]{100}'),
            'model_id' => fake()->randomNumber(),
            'ancien_valeurs' => '{}',
            'nouvelles_valeurs' => '{}',
            'ip_address' => fake()->regexify('[A-Za-z0-9]{45}'),
            'user_agent' => fake()->text(),
        ];
    }
}
