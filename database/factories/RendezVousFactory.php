<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RendezVousFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'medecin_id' => User::factory(),
            'service_id' => Service::factory(),
            'date_heure' => fake()->dateTime(),
            'duree_minutes' => fake()->randomNumber(),
            'type' => fake()->randomElement(["consultation","suivi","examen","chirurgie","vaccination"]),
            'motif' => fake()->text(),
            'notes' => fake()->text(),
            'statut' => fake()->randomElement(["planifie","confirme","en_attente","en_cours","termine","annule","absent"]),
        ];
    }
}
