<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CarePlanStatus;
use App\Models\CarePlan;
use App\Models\User;

/**
 * Who can do what with a CarePlan. Per references/rbac/matrix.md (M1 row):
 *   intervenant  → view assigned beneficiary's plans (read-only)
 *   coordinateur → full CRUD within structure
 *   dirigeant    → view + update + archive within structure (no create per matrix)
 *   referent_qualite → view only
 *
 * BasePolicy::before() runs the tenant ownership check first — a plan from
 * another structure is invisible regardless of role.
 */
class CarePlanPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('care_plans.view');
    }

    public function view(User $user, CarePlan $plan): bool
    {
        return $user->hasPermissionTo('care_plans.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('care_plans.create');
    }

    public function update(User $user, CarePlan $plan): bool
    {
        if ($plan->status === CarePlanStatus::Archived) {
            return false;
        }

        return $user->hasPermissionTo('care_plans.update');
    }

    public function archive(User $user, CarePlan $plan): bool
    {
        if ($plan->status === CarePlanStatus::Archived) {
            return false;
        }

        return $user->hasPermissionTo('care_plans.archive');
    }

    public function copyTemplate(User $user, CarePlan $plan): bool
    {
        return $user->hasPermissionTo('care_plans.copy_template');
    }

    public function delete(User $user, CarePlan $plan): bool
    {
        return $user->hasRole('dirigeant');
    }
}
