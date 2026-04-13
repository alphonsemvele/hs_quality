<?php

namespace Database\Factories;

use App\Models\Admission;
use App\Models\Patient;
use App\Models\RendezVou;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsultationFactory extends Factory
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
            'admission_id' => Admission::factory(),
            'rendez_vous_id' => RendezVou::factory(),
            'service_id' => Service::factory(),
            'date_consultation' => fake()->dateTime(),
            'motif' => fake()->text(),
            'histoire_maladie' => fake()->text(),
            'examen_clinique' => fake()->text(),
            'hypotheses_diagnostiques' => '{}',
            'diagnostic_principal' => fake()->regexify('[A-Za-z0-9]{255}'),
            'diagnostics_secondaires' => '{}',
            'conduite_a_tenir' => fake()->text(),
            'recommandations' => fake()->text(),
            'prochain_rdv' => fake()->date(),
            'duree_minutes' => fake()->randomNumber(),
            'statut' => fake()->randomElement(["en_cours","termine","annule"]),
        ];
    }
}
