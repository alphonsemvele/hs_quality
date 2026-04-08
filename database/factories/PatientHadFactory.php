<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientHadFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'medecin_referent_id' => User::factory(),
            'date_inclusion' => fake()->date(),
            'date_sortie' => fake()->date(),
            'motif_inclusion' => fake()->text(),
            'motif_sortie' => fake()->text(),
            'adresse_domicile' => fake()->text(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'personne_reference_nom' => fake()->regexify('[A-Za-z0-9]{100}'),
            'personne_reference_telephone' => fake()->regexify('[A-Za-z0-9]{20}'),
            'personne_reference_lien' => fake()->regexify('[A-Za-z0-9]{50}'),
            'protocole_soins' => fake()->text(),
            'frequence_visites' => fake()->randomElement(["quotidien","2_fois_semaine","hebdomadaire","bimensuel"]),
            'equipements_domicile' => '{}',
            'notes' => fake()->text(),
            'statut' => fake()->randomElement(["actif","suspendu","termine"]),
        ];
    }
}
