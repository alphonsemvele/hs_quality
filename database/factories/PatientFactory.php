<?php

namespace Database\Factories;

use App\Models\Assurance;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'numero_dossier' => fake()->regexify('[A-Za-z0-9]{20}'),
            'nom' => fake()->regexify('[A-Za-z0-9]{100}'),
            'prenom' => fake()->regexify('[A-Za-z0-9]{100}'),
            'date_naissance' => fake()->date(),
            'sexe' => fake()->randomElement(["M","F"]),
            'lieu_naissance' => fake()->regexify('[A-Za-z0-9]{100}'),
            'nationalite' => fake()->regexify('[A-Za-z0-9]{50}'),
            'cni' => fake()->regexify('[A-Za-z0-9]{30}'),
            'telephone' => fake()->regexify('[A-Za-z0-9]{20}'),
            'telephone_urgence' => fake()->regexify('[A-Za-z0-9]{20}'),
            'email' => fake()->safeEmail(),
            'adresse' => fake()->text(),
            'ville' => fake()->regexify('[A-Za-z0-9]{50}'),
            'quartier' => fake()->regexify('[A-Za-z0-9]{100}'),
            'profession' => fake()->regexify('[A-Za-z0-9]{100}'),
            'situation_matrimoniale' => fake()->randomElement(["celibataire","marie","divorce","veuf"]),
            'groupe_sanguin' => fake()->randomElement(["A+","A-","B+","B-","AB+","AB-","O+","O-"]),
            'allergies' => '{}',
            'antecedents_medicaux' => '{}',
            'antecedents_chirurgicaux' => '{}',
            'antecedents_familiaux' => '{}',
            'assurance_id' => Assurance::factory(),
            'numero_assurance' => fake()->regexify('[A-Za-z0-9]{50}'),
            'personne_contact_nom' => fake()->regexify('[A-Za-z0-9]{100}'),
            'personne_contact_telephone' => fake()->regexify('[A-Za-z0-9]{20}'),
            'personne_contact_lien' => fake()->regexify('[A-Za-z0-9]{50}'),
            'photo' => fake()->word(),
            'notes' => fake()->text(),
            'statut' => fake()->randomElement(["actif","inactif","decede"]),
        ];
    }
}
