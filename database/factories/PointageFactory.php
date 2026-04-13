<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointageFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => fake()->date(),
            'heure_arrivee' => fake()->dateTime(),
            'heure_depart' => fake()->dateTime(),
            'type' => fake()->randomElement(["normal","garde","astreinte"]),
            'equipe' => fake()->randomElement(["matin","apres_midi","nuit"]),
            'source' => fake()->randomElement(["manuel","biometrique","mobile"]),
            'localisation' => fake()->regexify('[A-Za-z0-9]{255}'),
            'notes' => fake()->text(),
            'statut' => fake()->randomElement(["present","absent","retard","conge","maladie"]),
        ];
    }
}
