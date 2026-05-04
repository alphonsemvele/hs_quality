<?php

namespace Database\Factories;

use App\Models\Habilitation;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Habilitation>
 */
class HabilitationFactory extends Factory
{
    protected $model = Habilitation::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['DEAS', 'AS', 'IDEL', 'AVS', 'AMP']),
            'reference_number' => 'RNCP-'.fake()->numerify('#####'),
            'valid_from' => fake()->dateTimeBetween('-10 years', '-1 year'),
            'valid_until' => null,
            'evidence_path' => null,
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

    public function ofType(string $type): self
    {
        return $this->state(['type' => $type]);
    }
}
