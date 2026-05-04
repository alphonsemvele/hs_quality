<?php

namespace Database\Factories;

use App\Enums\TrainingPlanStatus;
use App\Models\Structure;
use App\Models\TrainingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingPlan>
 */
class TrainingPlanFactory extends Factory
{
    protected $model = TrainingPlan::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'year' => 2026,
            'theme' => fake()->randomElement([
                'Bientraitance et HAS',
                'Premiers secours et gestes urgence',
                'Hygiène à domicile',
                'Coordination pluridisciplinaire',
            ]),
            'target_audience' => fake('fr_FR')->sentence(),
            'status' => TrainingPlanStatus::Draft,
            'created_by' => null,
            'published_at' => null,
            'archived_at' => null,
        ];
    }

    public function forStructure(Structure $structure): self
    {
        return $this->state(['structure_id' => $structure->id]);
    }

    public function published(): self
    {
        return $this->state([
            'status' => TrainingPlanStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function archived(): self
    {
        return $this->state([
            'status' => TrainingPlanStatus::Archived,
            'archived_at' => now(),
        ]);
    }
}
