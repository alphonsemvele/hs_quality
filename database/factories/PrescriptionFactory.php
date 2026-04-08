<?php

namespace Database\Factories;

use App\Models\Admission;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrescriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'numero' => fake()->regexify('[A-Za-z0-9]{20}'),
            'patient_id' => Patient::factory(),
            'medecin_id' => User::factory(),
            'consultation_id' => Consultation::factory(),
            'admission_id' => Admission::factory(),
            'date_prescription' => fake()->dateTime(),
            'date_validite' => fake()->date(),
            'instructions_generales' => fake()->text(),
            'statut' => fake()->randomElement(["en_attente","partiellement_delivree","delivree","annulee"]),
        ];
    }
}
