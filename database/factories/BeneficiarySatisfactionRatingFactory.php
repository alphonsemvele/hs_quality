<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\BeneficiarySatisfactionRating;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeneficiarySatisfactionRating>
 */
class BeneficiarySatisfactionRatingFactory extends Factory
{
    protected $model = BeneficiarySatisfactionRating::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'beneficiary_id' => Beneficiary::factory(),
            'intervention_id' => null,
            'score' => fake()->numberBetween(3, 5),
            'comment' => fake()->optional()->sentence(),
            'rated_at' => now()->subDays(fake()->numberBetween(0, 30))->toDateString(),
            'rated_by' => null,
        ];
    }

    public function forStructure(Structure $structure): static
    {
        return $this->state(fn () => ['structure_id' => $structure->id]);
    }

    public function forBeneficiary(Beneficiary $beneficiary): static
    {
        return $this->state(fn () => [
            'structure_id' => $beneficiary->structure_id,
            'beneficiary_id' => $beneficiary->id,
        ]);
    }

    public function withScore(int $score): static
    {
        return $this->state(fn () => ['score' => $score]);
    }

    public function ratedBy(User $user): static
    {
        return $this->state(fn () => ['rated_by' => $user->id]);
    }
}
