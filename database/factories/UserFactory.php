<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake('fr_FR')->firstName(),
            'lastname' => fake('fr_FR')->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'telephone' => fake('fr_FR')->mobileNumber(),
            'avatar' => null,
            'matricule' => strtoupper(Str::random(8)),
            'fonction' => fake()->randomElement(['intervenant', 'coordinateur', 'dirigeant', 'referent_qualite', 'rh']),
            'specialite' => null,
            'service_id' => null,
            'date_embauche' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'statut' => 'actif',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
