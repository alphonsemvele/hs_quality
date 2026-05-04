<?php

namespace App\Policies;

use App\Models\TrainingPlan;
use App\Models\User;

/**
 * Training plan policy.
 *   viewAny / view — anyone in the structure (intervenants need to see
 *                    the plan to know what they can sign up for)
 *   create         — trainings.plan
 *   update/publish — trainings.plan
 *   archive/delete — trainings.plan
 */
class TrainingPlanPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TrainingPlan $plan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('trainings.plan');
    }

    public function update(User $user, TrainingPlan $plan): bool
    {
        return $user->hasPermissionTo('trainings.plan');
    }

    public function publish(User $user, TrainingPlan $plan): bool
    {
        return $user->hasPermissionTo('trainings.plan');
    }

    public function archive(User $user, TrainingPlan $plan): bool
    {
        return $user->hasPermissionTo('trainings.plan');
    }

    public function delete(User $user, TrainingPlan $plan): bool
    {
        return $user->hasPermissionTo('trainings.plan');
    }
}
