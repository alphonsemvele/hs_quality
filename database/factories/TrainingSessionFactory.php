<?php

namespace Database\Factories;

use App\Models\Structure;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSession>
 */
class TrainingSessionFactory extends Factory
{
    protected $model = TrainingSession::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+3 months');

        return [
            'structure_id' => Structure::factory(),
            'training_plan_id' => TrainingPlan::factory(),
            'title' => fake('fr_FR')->sentence(4),
            'trainer_name' => fake('fr_FR')->name(),
            'trainer_user_id' => null,
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+3 hours'),
            'capacity' => fake()->numberBetween(8, 30),
            'location' => fake('fr_FR')->city(),
        ];
    }

    public function forPlan(TrainingPlan $plan): self
    {
        return $this->state([
            'structure_id' => $plan->structure_id,
            'training_plan_id' => $plan->id,
        ]);
    }
}
