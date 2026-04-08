<?php

namespace Database\Factories;

use App\Models\PatientHad;
use App\Models\Tournee;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisiteHadFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tournee_id' => Tournee::factory(),
            'patient_had_id' => PatientHad::factory(),
            'ordre' => fake()->randomNumber(),
            'heure_prevue' => fake()->time(),
            'heure_arrivee' => fake()->time(),
            'heure_depart' => fake()->time(),
            'latitude_arrivee' => fake()->randomFloat(7, 0, 999.9999999),
            'longitude_arrivee' => fake()->randomFloat(7, 0, 999.9999999),
            'soins_realises' => '{}',
            'observations' => fake()->text(),
            'constantes' => '{}',
            'alertes' => '{}',
            'signature_patient' => fake()->text(),
            'photos' => '{}',
            'statut' => fake()->randomElement(["planifiee","en_cours","terminee","annulee","reportee"]),
        ];
    }
}
