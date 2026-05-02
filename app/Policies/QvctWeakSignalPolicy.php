<?php

namespace App\Policies;

use App\Models\QvctWeakSignal;
use App\Models\User;

/**
 * Weak signals — RH / dirigeant / référent qualité view + acknowledge.
 * Intervenants never see them (signals describe their own collective
 * morale; surfacing back to the source could chill responses).
 */
class QvctWeakSignalPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('qvct.alert.receive');
    }

    public function view(User $user, QvctWeakSignal $signal): bool
    {
        return $user->hasPermissionTo('qvct.alert.receive');
    }

    public function acknowledge(User $user, QvctWeakSignal $signal): bool
    {
        return $user->hasPermissionTo('qvct.weak_signal.acknowledge');
    }

    public function delete(User $user, QvctWeakSignal $signal): bool
    {
        return false;
    }
}
