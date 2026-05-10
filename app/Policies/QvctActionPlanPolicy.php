<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QvctActionPlan;
use App\Models\User;

/**
 * Action plan = RH-owned. Editable by RH; published plans are visible
 * to anyone with QVCT structure-aggregate visibility (so coordinateurs
 * and dirigeants see the plan items affecting their teams).
 */
class QvctActionPlanPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'qvct.view.team_aggregates',
            'qvct.view.structure_aggregates',
            'qvct.action_plan.update',
        ]);
    }

    public function view(User $user, QvctActionPlan $plan): bool
    {
        if ($user->hasPermissionTo('qvct.action_plan.update')) {
            return true;
        }

        // Non-RH users see only published or closed plans (drafts stay
        // RH-internal). Coordinateurs see via team_aggregates;
        // dirigeants via structure_aggregates.
        $hasViewerPerm = $user->hasAnyPermission([
            'qvct.view.team_aggregates',
            'qvct.view.structure_aggregates',
        ]);

        return $hasViewerPerm && ! $plan->isDraft();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('qvct.action_plan.update');
    }

    public function update(User $user, QvctActionPlan $plan): bool
    {
        return $user->hasPermissionTo('qvct.action_plan.update');
    }

    public function delete(User $user, QvctActionPlan $plan): bool
    {
        return $user->hasPermissionTo('qvct.action_plan.update');
    }
}
