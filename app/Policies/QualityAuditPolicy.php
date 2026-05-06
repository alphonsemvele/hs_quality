<?php

namespace App\Policies;

use App\Models\QualityAudit;
use App\Models\User;

class QualityAuditPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('audits.view');
    }

    public function view(User $user, QualityAudit $audit): bool
    {
        return $user->hasPermissionTo('audits.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('audits.configure');
    }

    public function update(User $user, QualityAudit $audit): bool
    {
        if ($audit->isTerminal()) {
            return false;
        }

        return $user->hasPermissionTo('audits.configure');
    }

    public function execute(User $user, QualityAudit $audit): bool
    {
        if ($audit->isTerminal()) {
            return false;
        }

        return $user->hasPermissionTo('audits.execute')
            || $user->hasPermissionTo('audits.configure');
    }

    public function finalize(User $user, QualityAudit $audit): bool
    {
        if ($audit->isTerminal()) {
            return false;
        }

        return $user->hasPermissionTo('audits.execute')
            || $user->hasPermissionTo('audits.configure');
    }

    public function cancel(User $user, QualityAudit $audit): bool
    {
        if ($audit->isTerminal()) {
            return false;
        }

        return $user->hasPermissionTo('audits.configure');
    }

    public function delete(User $user, QualityAudit $audit): bool
    {
        return $user->hasPermissionTo('audits.configure');
    }
}
