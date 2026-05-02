<?php

namespace App\Policies;

use App\Models\PacAction;
use App\Models\User;

/**
 * PAC action item. Updateable by anyone with pac.update OR by the
 * action's responsible_user (their lane to drive completion).
 * Same pattern as QvctActionPlanItemPolicy.
 */
class PacActionPolicy extends BasePolicy
{
    public function view(User $user, PacAction $action): bool
    {
        return $user->hasPermissionTo('audits.view');
    }

    public function update(User $user, PacAction $action): bool
    {
        if ($user->hasPermissionTo('pac.update')) {
            return true;
        }

        return $action->responsible_user_id === $user->id;
    }

    public function delete(User $user, PacAction $action): bool
    {
        return $user->hasPermissionTo('pac.update');
    }
}
