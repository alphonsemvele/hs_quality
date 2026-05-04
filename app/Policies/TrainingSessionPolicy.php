<?php

namespace App\Policies;

use App\Models\TrainingSession;
use App\Models\User;

/**
 * Training session policy.
 *   view   — any user in the structure
 *   create — trainings.plan (adding sessions is plan-side editing)
 *   update — trainings.plan (re-schedule, change capacity)
 *   delete — trainings.plan
 */
class TrainingSessionPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TrainingSession $session): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('trainings.plan');
    }

    public function update(User $user, TrainingSession $session): bool
    {
        return $user->hasPermissionTo('trainings.plan');
    }

    public function delete(User $user, TrainingSession $session): bool
    {
        return $user->hasPermissionTo('trainings.plan');
    }
}
