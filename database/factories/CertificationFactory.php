<?php

namespace Database\Factories;

use App\Models\Certification;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certification>
 */
class CertificationFactory extends Factory
{
    protected $model = Certification::class;

    public function definition(): array
    {
        $issued = fake()->dateTimeBetween('-2 years', '-1 month');

        return [
            'structure_id' => Structure::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement([
                'BLS', 'gestes_urgence', 'habilitation_electrique', 'manipulation_medicaments',
            ]),
            'reference_number' => 'CERT-'.fake()->numerify('######'),
            'issued_on' => $issued,
            // Default to a 2-year validity window from issuance.
            'expires_at' => (clone $issued)->modify('+2 years'),
            'evidence_path' => null,
            'last_alerted_at' => null,
            'last_alert_window' => null,
            'recorded_by' => null,
        ];
    }

    public function forStructure(Structure $structure): self
    {
        return $this->state(['structure_id' => $structure->id]);
    }

    public function forUser(User $user): self
    {
        return $this->state([
            'structure_id' => $user->structure_id,
            'user_id' => $user->id,
        ]);
    }

    /** Expires N days from now (positive = future, negative = already expired). */
    public function expiresInDays(int $days): self
    {
        return $this->state([
            'expires_at' => now()->addDays($days)->toDateString(),
        ]);
    }
}
