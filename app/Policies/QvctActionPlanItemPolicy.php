<?php

namespace App\Policies;

use App\Models\QvctActionPlanItem;
use App\Models\User;

/**
 * Item-level policy. Anyone with action_plan.update may edit any item.
 * The responsible_user can also update status + record impact on items
 * assigned to them (their lane to drive completion).
 */
class QvctActionPlanItemPolicy extends BasePolicy
{
    public function view(User $user, QvctActionPlanItem $item): bool
    {
        if ($user->hasPermissionTo('qvct.action_plan.update')) {
            return true;
        }

        $hasViewerPerm = $user->hasAnyPermission([
            'qvct.view.team_aggregates',
            'qvct.view.structure_aggregates',
        ]);

        return $hasViewerPerm && ! $item->actionPlan->isDraft();
    }

    public function update(User $user, QvctActionPlanItem $item): bool
    {
        if ($user->hasPermissionTo('qvct.action_plan.update')) {
            return true;
        }

        // Responsible user can drive their own assigned item's status.
        return $item->responsible_user_id === $user->id;
    }

    public function delete(User $user, QvctActionPlanItem $item): bool
    {
        return $user->hasPermissionTo('qvct.action_plan.update');
    }
}
