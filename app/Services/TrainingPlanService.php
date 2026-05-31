<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TrainingAttendanceStatus;
use App\Enums\TrainingPlanStatus;
use App\Models\Structure;
use App\Models\TrainingAttendance;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\CarbonInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * TrainingPlanService — owns the lifecycle of training plans, sessions,
 * and attendances. Spec: PHASE2_PROGRESS.md M5.10.
 *
 * Plan lifecycle: draft → published → archived (one-way).
 *
 * Attendance lifecycle:
 *   register     → status=registered
 *   markAttended → status=attended,  attended_at=now()
 *   cancel       → status=cancelled, cancelled_at=now()
 *
 * Cancelled rows are preserved (not deleted) so the structure can
 * audit no-shows. Re-registering after cancel revives the row by
 * resetting status to `registered` — keeps the unique-(session,user)
 * constraint working without a soft-delete column.
 */
class TrainingPlanService
{
    // ── Plan lifecycle ──────────────────────────────────────────────

    public function draft(
        Structure $structure,
        User $author,
        int $year,
        string $theme,
        ?string $targetAudience = null,
    ): TrainingPlan {
        return TrainingPlan::create([
            'structure_id' => $structure->id,
            'created_by' => $author->id,
            'year' => $year,
            'theme' => $theme,
            'target_audience' => $targetAudience,
            'status' => TrainingPlanStatus::Draft,
        ]);
    }

    /**
     * Update a plan's metadata (year, theme, target_audience). Lifecycle
     * fields (status, published_at, archived_at) are NOT touched here —
     * use publish() / archive() for those transitions.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(TrainingPlan $plan, array $data): TrainingPlan
    {
        if ($plan->status === TrainingPlanStatus::Archived) {
            throw new HttpException(409, 'Impossible de modifier un plan archivé.');
        }

        $plan->update(array_intersect_key($data, array_flip(['year', 'theme', 'target_audience'])));

        return $plan->fresh();
    }

    public function publish(TrainingPlan $plan): TrainingPlan
    {
        if ($plan->status === TrainingPlanStatus::Archived) {
            throw new HttpException(409, 'Impossible de publier un plan archivé.');
        }

        if ($plan->status === TrainingPlanStatus::Published) {
            return $plan; // idempotent
        }

        $plan->update([
            'status' => TrainingPlanStatus::Published,
            'published_at' => now(),
        ]);

        return $plan->fresh();
    }

    public function archive(TrainingPlan $plan): TrainingPlan
    {
        if ($plan->status === TrainingPlanStatus::Archived) {
            return $plan;
        }

        $plan->update([
            'status' => TrainingPlanStatus::Archived,
            'archived_at' => now(),
        ]);

        return $plan->fresh();
    }

    // ── Sessions ───────────────────────────────────────────────────

    public function addSession(
        TrainingPlan $plan,
        string $title,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        int $capacity = 20,
        ?string $trainerName = null,
        ?User $trainer = null,
        ?string $location = null,
    ): TrainingSession {
        if ($plan->status === TrainingPlanStatus::Archived) {
            throw new HttpException(409, 'Impossible d’ajouter une session à un plan archivé.');
        }

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            throw new HttpException(422, 'La fin de la session doit être postérieure au début.');
        }

        return TrainingSession::create([
            'structure_id' => $plan->structure_id,
            'training_plan_id' => $plan->id,
            'title' => $title,
            'trainer_name' => $trainerName,
            'trainer_user_id' => $trainer?->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'capacity' => $capacity,
            'location' => $location,
        ]);
    }

    // ── Attendances ────────────────────────────────────────────────

    public function register(TrainingSession $session, User $user, ?User $recorder = null): TrainingAttendance
    {
        $existing = TrainingAttendance::query()
            ->where('training_session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing !== null) {
            // Re-registering after a cancel reactivates the row.
            $existing->update([
                'status' => TrainingAttendanceStatus::Registered,
                'cancelled_at' => null,
                'attended_at' => null,
                'recorded_by' => $recorder?->id ?? $existing->recorded_by,
            ]);

            return $existing->fresh();
        }

        if ($this->capacityReached($session)) {
            throw new HttpException(409, 'Session complète.');
        }

        return TrainingAttendance::create([
            'structure_id' => $session->structure_id,
            'training_session_id' => $session->id,
            'user_id' => $user->id,
            'status' => TrainingAttendanceStatus::Registered,
            'recorded_by' => $recorder?->id,
        ]);
    }

    public function markAttended(TrainingAttendance $attendance, ?string $notes = null): TrainingAttendance
    {
        $attendance->update([
            'status' => TrainingAttendanceStatus::Attended,
            'attended_at' => now(),
            'notes' => $notes ?? $attendance->notes,
        ]);

        return $attendance->fresh();
    }

    public function cancel(TrainingAttendance $attendance, ?string $notes = null): TrainingAttendance
    {
        $attendance->update([
            'status' => TrainingAttendanceStatus::Cancelled,
            'cancelled_at' => now(),
            'notes' => $notes ?? $attendance->notes,
        ]);

        return $attendance->fresh();
    }

    private function capacityReached(TrainingSession $session): bool
    {
        $registered = TrainingAttendance::query()
            ->where('training_session_id', $session->id)
            ->where('status', TrainingAttendanceStatus::Registered->value)
            ->count();

        return $registered >= $session->capacity;
    }
}
