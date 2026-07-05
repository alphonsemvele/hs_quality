<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccountDeletionStatus;
use App\Models\AccountDeletionRequest;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountDeletionRequest>
 */
class AccountDeletionRequestFactory extends Factory
{
    protected $model = AccountDeletionRequest::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'user_id' => User::factory(),
            'status' => AccountDeletionStatus::Pending->value,
            'requested_at' => now(),
            'effective_at' => now()->addDays(30),
        ];
    }

    public function due(): static
    {
        return $this->state(fn () => [
            'requested_at' => now()->subDays(31),
            'effective_at' => now()->subDay(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => AccountDeletionStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);
    }
}
