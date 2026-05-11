<?php

namespace Database\Factories;

use App\Enums\PacStatus;
use App\Models\Pac;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pac>
 */
class PacFactory extends Factory
{
    protected $model = Pac::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'audit_run_id' => null,
            'title' => 'PAC '.fake()->randomElement(['Q1', 'Q2', 'Q3', 'Q4']).'-2026',
            'description' => fake('fr_FR')->paragraph(),
            'status' => PacStatus::Draft->value,
            'target_period' => '2026-Q'.fake()->numberBetween(1, 4),
            'created_by' => null,
            'closed_at' => null,
            'closed_by' => null,
        ];
    }

    public function forStructure(Structure $structure): self
    {
        return $this->state(['structure_id' => $structure->id]);
    }

    public function active(): self
    {
        return $this->state(['status' => PacStatus::Active->value]);
    }

    public function closed(): self
    {
        return $this->state([
            'status' => PacStatus::Closed->value,
            'closed_at' => now(),
        ]);
    }
}
