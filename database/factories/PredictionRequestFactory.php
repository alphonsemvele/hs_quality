<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PredictionStatus;
use App\Enums\PredictionType;
use App\Models\PredictionRequest;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PredictionRequest>
 */
class PredictionRequestFactory extends Factory
{
    protected $model = PredictionRequest::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'requested_by_user_id' => function (array $attrs) {
                return User::factory()
                    ->state(['structure_id' => $attrs['structure_id']])
                    ->create()
                    ->id;
            },
            'type' => fake()->randomElement(PredictionType::cases())->value,
            'status' => PredictionStatus::EnAttente->value,
            'input_snapshot' => ['subject_id' => fake()->uuid()],
            'result' => null,
            'error_message' => null,
            'ml_job_id' => null,
            'requested_at' => now(),
            'completed_at' => null,
        ];
    }

    public function ofType(PredictionType $type): self
    {
        return $this->state(['type' => $type->value]);
    }

    public function pending(): self
    {
        return $this->state(['status' => PredictionStatus::EnAttente->value]);
    }

    public function processing(): self
    {
        return $this->state([
            'status' => PredictionStatus::EnTraitement->value,
            'ml_job_id' => fake()->uuid(),
        ]);
    }

    public function completed(array $result = []): self
    {
        return $this->state([
            'status' => PredictionStatus::Termine->value,
            'ml_job_id' => fake()->uuid(),
            'result' => $result ?: ['score' => fake()->randomFloat(2, 0, 1), 'label' => 'faible'],
            'completed_at' => now(),
        ]);
    }

    public function failed(): self
    {
        return $this->state([
            'status' => PredictionStatus::Echoue->value,
            'ml_job_id' => fake()->uuid(),
            'error_message' => 'ML service returned HTTP 500',
            'completed_at' => now(),
        ]);
    }

    public function forStructure(Structure $structure): self
    {
        return $this->state(['structure_id' => $structure->id]);
    }
}
