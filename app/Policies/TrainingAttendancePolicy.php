<?php

namespace App\Policies;

use App\Models\TrainingAttendance;
use App\Models\User;

/**
 * Training attendance policy.
 *   view    — the user themselves OR trainings.record (RH/coord/dirigeant)
 *   create  — any user in the structure (registering for a session is
 *             a self-service action)
 *   update  — trainings.record (only the trainer/RH marks attended/cancelled)
 *   delete  — trainings.record (rare — usually cancel instead)
 */
class TrainingAttendancePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TrainingAttendance $attendance): bool
    {
        return $attendance->user_id === $user->id
            || $user->hasPermissionTo('trainings.record');
    }

    public function create(User $user): bool
    {
        // Self-service registration. The service enforces capacity + dedup.
        return true;
    }

    public function update(User $user, TrainingAttendance $attendance): bool
    {
        return $user->hasPermissionTo('trainings.record');
    }

    public function delete(User $user, TrainingAttendance $attendance): bool
    {
        return $user->hasPermissionTo('trainings.record');
    }
}
