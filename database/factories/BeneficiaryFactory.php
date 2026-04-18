<?php

namespace Database\Factories;

use App\Enums\BeneficiaryStatus;
use App\Enums\Gender;
use App\Models\Beneficiary;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Beneficiary>
 */
class BeneficiaryFactory extends Factory
{
    protected $model = Beneficiary::class;

    public function definition(): array
    {
        $gender = fake()->randomElement([Gender::Male->value, Gender::Female->value]);
        $dob = fake()->dateTimeBetween('-95 years', '-60 years');

        return [
            'structure_id' => Structure::factory(),
            'first_name' => $gender === Gender::Male->value
                ? fake('fr_FR')->firstNameMale()
                : fake('fr_FR')->firstNameFemale(),
            'last_name' => strtoupper(fake('fr_FR')->lastName()),
            'date_of_birth' => $dob->format('Y-m-d'),
            'gender' => $gender,
            'address' => fake('fr_FR')->streetAddress(),
            'postal_code' => fake('fr_FR')->postcode(),
            'city' => fake('fr_FR')->city(),
            'phone' => fake('fr_FR')->phoneNumber(),
            'email' => null,
            'marital_status' => fake()->randomElement(['célibataire', 'marié(e)', 'veuf/veuve', 'divorcé(e)']),
            'gir' => fake()->numberBetween(1, 6),
            'primary_doctor' => 'Dr '.fake('fr_FR')->lastName(),
            'primary_doctor_phone' => fake('fr_FR')->phoneNumber(),
            'emergency_contact_name' => fake('fr_FR')->name(),
            'emergency_contact_phone' => fake('fr_FR')->phoneNumber(),
            'emergency_contact_relationship' => fake()->randomElement(['fils', 'fille', 'conjoint', 'neveu', 'nièce']),
            'medical_notes' => null,
            'allergies' => null,
            'medical_history' => null,
            'current_treatments' => null,
            'status' => BeneficiaryStatus::Active->value,
            'admitted_at' => fake()->dateTimeBetween('-5 years', '-1 week')->format('Y-m-d'),
            'exited_at' => null,
            'exit_reason' => null,
        ];
    }

    public function forStructure(Structure $structure): static
    {
        return $this->state(fn () => ['structure_id' => $structure->id]);
    }

    public function withMedicalNotes(string $notes = 'Allergie arachide connue. Surveillance tension artérielle.'): static
    {
        return $this->state(fn () => [
            'medical_notes' => $notes,
            'allergies' => 'Arachide',
            'medical_history' => 'Hypertension, AVC léger en 2023',
            'current_treatments' => 'Aprovel 150mg matin, Kardegic 75mg soir',
        ]);
    }

    public function gir(int $level): static
    {
        return $this->state(fn () => ['gir' => max(1, min(6, $level))]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => BeneficiaryStatus::Inactive->value]);
    }

    public function discharged(): static
    {
        return $this->state(fn () => [
            'status' => BeneficiaryStatus::Discharged->value,
            'exited_at' => now()->subDays(30)->format('Y-m-d'),
            'exit_reason' => 'Déménagement hors secteur',
        ]);
    }
}
