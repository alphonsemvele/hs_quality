<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnnualReportStatus;
use App\Models\AnnualReport;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnnualReport>
 */
class AnnualReportFactory extends Factory
{
    protected $model = AnnualReport::class;

    public function definition(): array
    {
        $structure = Structure::factory();

        return [
            'structure_id' => $structure,
            'year' => fake()->numberBetween(2024, now()->year),
            'status' => AnnualReportStatus::EnAttente->value,
            'pdf_path' => null,
            'pdf_generated_at' => null,
            'requested_by_user_id' => function (array $attrs) {
                return User::factory()->state(['structure_id' => $attrs['structure_id']])->create()->id;
            },
        ];
    }

    public function generated(): self
    {
        return $this->state([
            'status' => AnnualReportStatus::Genere->value,
            'pdf_path' => 'annual-reports/'.fake()->uuid().'/2025-'.fake()->uuid().'.pdf',
            'pdf_generated_at' => now(),
        ]);
    }
}
