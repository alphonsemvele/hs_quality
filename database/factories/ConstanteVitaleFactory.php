<?php

namespace Database\Factories;

use App\Models\Admission;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConstanteVitaleFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'admission_id' => Admission::factory(),
            'user_id' => User::factory(),
            'date_mesure' => fake()->dateTime(),
            'poids' => fake()->randomFloat(2, 0, 999.99),
            'taille' => fake()->randomFloat(2, 0, 999.99),
            'temperature' => fake()->randomFloat(2, 0, 99.99),
            'tension_systolique' => fake()->randomNumber(),
            'tension_diastolique' => fake()->randomNumber(),
            'frequence_cardiaque' => fake()->randomNumber(),
            'frequence_respiratoire' => fake()->randomNumber(),
            'saturation_oxygene' => fake()->randomNumber(),
            'glycemie' => fake()->randomFloat(2, 0, 999.99),
            'score_douleur' => fake()->randomNumber(),
            'notes' => fake()->text(),
        ];
    }
}
