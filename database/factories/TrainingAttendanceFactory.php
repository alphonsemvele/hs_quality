<?php

namespace Database\Factories;

use App\Enums\TrainingAttendanceStatus;
use App\Models\Structure;
use App\Models\TrainingAttendance;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingAttendance>
 */
class TrainingAttendanceFactory extends Factory
{
    protected $model = TrainingAttendance::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'training_session_id' => TrainingSession::factory(),
            'user_id' => User::factory(),
            'status' => TrainingAttendanceStatus::Registered,
            'notes' => null,
            'recorded_by' => null,
            'attended_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function forSession(TrainingSession $session, ?User $user = null): self
    {
        // Use a closure for user_id so each row in count(N) gets its own
        // freshly-created user. Without the closure, the factory cache
        // would reuse the same user_id and trip the unique (session, user)
        // constraint on the second insert.
        return $this->state(fn () => [
            'structure_id' => $session->structure_id,
            'training_session_id' => $session->id,
            'user_id' => $user?->id ?? User::factory()->forStructure($session->structure)->create()->id,
        ]);
    }

    public function attended(): self
    {
        return $this->state([
            'status' => TrainingAttendanceStatus::Attended,
            'attended_at' => now(),
        ]);
    }

    public function cancelled(): self
    {
        return $this->state([
            'status' => TrainingAttendanceStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
