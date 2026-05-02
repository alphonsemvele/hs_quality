<?php

namespace App\Policies;

use App\Models\QvctIndicator;
use App\Models\User;

/**
 * QVCT indicators are dashboard data — view by anyone in the structure
 * with structure-aggregate visibility (rh / dirigeant / référent qualité).
 * Editable by RH only (qvct.questionnaire.manage doubles as the QVCT-
 * data ownership perm).
 */
class QvctIndicatorPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('qvct.view.structure_aggregates');
    }

    public function view(User $user, QvctIndicator $indicator): bool
    {
        return $user->hasPermissionTo('qvct.view.structure_aggregates');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('qvct.questionnaire.manage');
    }

    public function update(User $user, QvctIndicator $indicator): bool
    {
        return $user->hasPermissionTo('qvct.questionnaire.manage');
    }

    public function delete(User $user, QvctIndicator $indicator): bool
    {
        return $user->hasPermissionTo('qvct.questionnaire.manage');
    }
}
