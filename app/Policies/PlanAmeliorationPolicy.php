<?php

namespace App\Policies;

use App\Models\PlanAmelioration;
use App\Models\User;

class PlanAmeliorationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['pac.generate', 'pac.update', 'audits.view']);
    }

    public function view(User $user, PlanAmelioration $plan): bool
    {
        return $user->hasAnyPermission(['pac.generate', 'pac.update', 'audits.view']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('pac.generate');
    }

    public function update(User $user, PlanAmelioration $plan): bool
    {
        if ($plan->isTerminal()) {
            return false;
        }

        return $user->hasPermissionTo('pac.update');
    }

    public function close(User $user, PlanAmelioration $plan): bool
    {
        if ($plan->isTerminal()) {
            return false;
        }

        return $user->hasPermissionTo('pac.close');
    }

    public function cancel(User $user, PlanAmelioration $plan): bool
    {
        if ($plan->isTerminal()) {
            return false;
        }

        return $user->hasPermissionTo('pac.update');
    }

    public function delete(User $user, PlanAmelioration $plan): bool
    {
        return $user->hasPermissionTo('pac.update');
    }
}
