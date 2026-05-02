<?php

namespace Database\Factories;

use App\Models\QvctIndicator;
use App\Models\Structure;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QvctIndicator>
 */
class QvctIndicatorFactory extends Factory
{
    protected $model = QvctIndicator::class;

    public function definition(): array
    {
        // Randomize across past 24 months so factory()->count(N) for the
        // same structure rarely hits the (structure_id, period_start)
        // unique constraint. Tests that need deterministic months should
        // use ->forPeriod() or ->sequence().
        $monthsBack = fake()->numberBetween(0, 23);
        $start = now()->startOfMonth()->subMonths($monthsBack);

        return [
            'structure_id' => Structure::factory(),
            'period_start' => $start->toDateString(),
            'period_end' => $start->copy()->endOfMonth()->toDateString(),
            'absenteeism_rate' => fake()->randomFloat(2, 0, 15),
            'turnover_rate' => fake()->randomFloat(2, 0, 30),
            'work_accidents_count' => fake()->numberBetween(0, 5),
            'barometer_mean_score' => fake()->randomFloat(2, 1, 5),
            'notes' => null,
            'captured_by' => null,
            'captured_at' => now(),
        ];
    }

    public function forStructure(Structure $structure): self
    {
        return $this->state(['structure_id' => $structure->id]);
    }

    public function forPeriod(string $startIso): self
    {
        $start = Carbon::parse($startIso)->startOfMonth();

        return $this->state([
            'period_start' => $start->toDateString(),
            'period_end' => $start->copy()->endOfMonth()->toDateString(),
        ]);
    }
}
