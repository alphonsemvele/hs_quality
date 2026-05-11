<?php

namespace Database\Factories;

use App\Enums\AuditRunStatus;
use App\Models\AuditGrid;
use App\Models\AuditRun;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditRun>
 */
class AuditRunFactory extends Factory
{
    protected $model = AuditRun::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'audit_grid_id' => AuditGrid::factory(),
            'title' => 'Évaluation interne '.fake()->randomElement(['Q1', 'Q2', 'Q3', 'Q4']).'-2026',
            'run_date' => fake()->dateTimeBetween('-3 months', '+1 month')->format('Y-m-d'),
            'status' => AuditRunStatus::Draft->value,
            'score' => null,
            'max_score' => null,
            'finalised_by' => null,
            'finalised_at' => null,
        ];
    }

    public function forGrid(AuditGrid $grid): self
    {
        return $this->state([
            'structure_id' => $grid->structure_id,
            'audit_grid_id' => $grid->id,
        ]);
    }

    public function inProgress(): self
    {
        return $this->state(['status' => AuditRunStatus::InProgress->value]);
    }

    public function finalised(): self
    {
        return $this->state([
            'status' => AuditRunStatus::Finalised->value,
            'finalised_at' => now(),
        ]);
    }
}
