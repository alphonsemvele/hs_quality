<?php

namespace App\Policies;

use App\Enums\CarePlanStatus;
use App\Models\PlannedTask;
use App\Models\User;

/**
 * Authorisation for PlannedTask. Tasks are owned by their parent care plan;
 * the right to mutate a task tracks the right to update the plan
 * (`care_plans.update`). BasePolicy::before() checks tenant ownership of
 * the task row first.
 *
 * Read access lives on the parent plan — this policy only covers create /
 * update / delete because list / show endpoints expose tasks via the plan
 * (no standalone GET on a task row).
 */
class PlannedTaskPolicy extends BasePolicy
{
    public function create(User $user, PlannedTask $task): bool
    {
        return $this->canMutate($user, $task);
    }

    public function update(User $user, PlannedTask $task): bool
    {
        return $this->canMutate($user, $task);
    }

    public function delete(User $user, PlannedTask $task): bool
    {
        return $this->canMutate($user, $task);
    }

    private function canMutate(User $user, PlannedTask $task): bool
    {
        if ($task->carePlan?->status === CarePlanStatus::Archived) {
            return false;
        }

        return $user->hasPermissionTo('care_plans.update');
    }
}
