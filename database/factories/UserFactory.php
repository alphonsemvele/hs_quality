<?php

namespace Database\Factories;

use App\Enums\UserType;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'structure_id' => null,
            'first_name' => fake('fr_FR')->firstName(),
            'last_name' => fake('fr_FR')->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake('fr_FR')->mobileNumber(),
            'avatar' => null,
            'employee_number' => strtoupper(Str::random(8)),
            'type' => UserType::Intervenant->value,
            'specialty' => null,
            'hired_at' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function forStructure(Structure $structure): static
    {
        return $this->state(fn () => ['structure_id' => $structure->id]);
    }

    public function intervenant(): static
    {
        return $this->state(fn () => ['type' => UserType::Intervenant->value]);
    }

    public function coordinateur(): static
    {
        return $this->state(fn () => ['type' => UserType::Coordinateur->value]);
    }

    public function dirigeant(): static
    {
        return $this->state(fn () => ['type' => UserType::Dirigeant->value]);
    }

    public function referentQualite(): static
    {
        return $this->state(fn () => ['type' => UserType::ReferentQualite->value]);
    }

    public function rh(): static
    {
        return $this->state(fn () => ['type' => UserType::Rh->value]);
    }

    public function beneficiairePortal(): static
    {
        return $this->state(fn () => ['type' => UserType::BeneficiairePortal->value]);
    }
}
