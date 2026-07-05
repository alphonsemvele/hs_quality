<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DataExportStatus;
use App\Models\DataExportRequest;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataExportRequest>
 */
class DataExportRequestFactory extends Factory
{
    protected $model = DataExportRequest::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'user_id' => User::factory(),
            'status' => DataExportStatus::Pending->value,
        ];
    }

    public function ready(): static
    {
        return $this->state(fn () => [
            'status' => DataExportStatus::Ready->value,
            'archive_disk' => 's3',
            'archive_path' => 'gdpr-exports/'.fake()->uuid().'.zip',
            'archive_size_bytes' => fake()->numberBetween(10_000, 5_000_000),
            'processed_at' => now()->subMinutes(5),
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => DataExportStatus::Failed->value,
            'failure_reason' => 'S3 upload failed',
            'processed_at' => now()->subMinutes(2),
        ]);
    }
}
