<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditGrid;
use App\Models\User;

/**
 * Audit grids = templates owned by the structure. Coordinateurs and
 * référents qualité view; configure (create/edit/delete) limited to
 * those with `audits.configure` (dirigeant + référent qualité).
 */
class AuditGridPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('audits.view');
    }

    public function view(User $user, AuditGrid $grid): bool
    {
        return $user->hasPermissionTo('audits.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('audits.configure');
    }

    public function update(User $user, AuditGrid $grid): bool
    {
        return $user->hasPermissionTo('audits.configure');
    }

    public function delete(User $user, AuditGrid $grid): bool
    {
        return $user->hasPermissionTo('audits.configure');
    }
}
