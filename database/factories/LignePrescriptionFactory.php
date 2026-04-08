<?php

namespace Database\Factories;

use App\Models\Medicament;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

class LignePrescriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'prescription_id' => Prescription::factory(),
            'medicament_id' => Medicament::factory(),
            'posologie' => fake()->regexify('[A-Za-z0-9]{255}'),
            'quantite_prescrite' => fake()->randomNumber(),
            'quantite_delivree' => fake()->randomNumber(),
            'duree_jours' => fake()->randomNumber(),
            'voie_administration' => fake()->regexify('[A-Za-z0-9]{50}'),
            'instructions' => fake()->text(),
        ];
    }
}
