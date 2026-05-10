<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditRun;
use App\Models\User;

/**
 * Audit run = an in-flight or finalised execution of a grid.
 *   view      — anyone with audits.view
 *   start/update — audits.configure (admin) OR audits.execute (auditor)
 *   finalise  — audits.execute (the auditor doing the run finalises it)
 *   delete    — audits.configure
 *
 * Lifecycle invariants (cannot record on finalised, etc.) live in the
 * service. The policy only decides "may this user act on this run".
 */
class AuditRunPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('audits.view');
    }

    public function view(User $user, AuditRun $run): bool
    {
        return $user->hasPermissionTo('audits.view');
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['audits.configure', 'audits.execute']);
    }

    public function update(User $user, AuditRun $run): bool
    {
        return $user->hasAnyPermission(['audits.configure', 'audits.execute']);
    }

    public function finalise(User $user, AuditRun $run): bool
    {
        return $user->hasPermissionTo('audits.execute');
    }

    public function delete(User $user, AuditRun $run): bool
    {
        return $user->hasPermissionTo('audits.configure');
    }
}
