<?php

namespace Database\Factories;

use App\Enums\QvctActionPlanStatus;
use App\Models\QvctActionPlan;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QvctActionPlan>
 */
class QvctActionPlanFactory extends Factory
{
    protected $model = QvctActionPlan::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'title' => 'Plan QVCT '.fake()->randomElement(['Q1', 'Q2', 'Q3', 'Q4']).'-2026',
            'description' => fake('fr_FR')->paragraph(),
            'status' => QvctActionPlanStatus::Draft->value,
            'target_quarter' => 'Q'.fake()->numberBetween(1, 4).'-2026',
            'created_by' => null,
            'published_by' => null,
            'published_at' => null,
            'closed_by' => null,
            'closed_at' => null,
        ];
    }

    public function forStructure(Structure $structure): self
    {
        return $this->state(['structure_id' => $structure->id]);
    }

    public function published(): self
    {
        return $this->state([
            'status' => QvctActionPlanStatus::Published->value,
            'published_at' => now(),
        ]);
    }

    public function closed(): self
    {
        return $this->state([
            'status' => QvctActionPlanStatus::Closed->value,
            'closed_at' => now(),
        ]);
    }
}
