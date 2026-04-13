<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nom' => fake()->regexify('[A-Za-z0-9]{100}'),
            'prenom' => fake()->regexify('[A-Za-z0-9]{100}'),
            'email' => fake()->safeEmail(),
            'email_verified_at' => fake()->dateTime(),
            'password' => fake()->password(),
            'telephone' => fake()->regexify('[A-Za-z0-9]{20}'),
            'avatar' => fake()->word(),
            'matricule' => fake()->regexify('[A-Za-z0-9]{20}'),
            'fonction' => fake()->randomElement(["medecin","infirmier","sage_femme","pharmacien","technicien","laborantin","administratif","receptionniste","comptable"]),
            'specialite' => fake()->regexify('[A-Za-z0-9]{100}'),
            'service_id' => Service::factory(),
            'date_embauche' => fake()->date(),
            'statut' => fake()->randomElement(["actif","inactif","conge","suspendu"]),
            'remember_token' => fake()->uuid(),
        ];
    }
}
