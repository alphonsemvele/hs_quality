<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SectorBenchmarkSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SectorBenchmarkSnapshot>
 */
class SectorBenchmarkSnapshotFactory extends Factory
{
    protected $model = SectorBenchmarkSnapshot::class;

    public function definition(): array
    {
        return [
            'snapshot_month' => now()->startOfMonth()->toDateString(),
            'interventions_data' => [],
            'incidents_data' => [],
            'qvct_data' => [],
            'conformity_data' => [],
            'generated_by_user_id' => null,
        ];
    }
}
