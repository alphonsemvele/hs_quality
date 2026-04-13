<?php

namespace Database\Factories;

use App\Models\Lit;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'numero' => fake()->regexify('[A-Za-z0-9]{20}'),
            'patient_id' => Patient::factory(),
            'service_id' => Service::factory(),
            'lit_id' => Lit::factory(),
            'medecin_id' => User::factory(),
            'type' => fake()->randomElement(["urgence","programmee","transfert"]),
            'motif' => fake()->text(),
            'diagnostic_entree' => fake()->text(),
            'diagnostic_sortie' => fake()->text(),
            'date_admission' => fake()->dateTime(),
            'date_sortie' => fake()->dateTime(),
            'mode_sortie' => fake()->randomElement(["guerison","amelioration","transfert","evasion","deces","contre_avis"]),
            'accompagnant_nom' => fake()->regexify('[A-Za-z0-9]{100}'),
            'accompagnant_telephone' => fake()->regexify('[A-Za-z0-9]{20}'),
            'accompagnant_lien' => fake()->regexify('[A-Za-z0-9]{50}'),
            'notes' => fake()->text(),
            'statut' => fake()->randomElement(["en_cours","termine","annule"]),
        ];
    }
}
