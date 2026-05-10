<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditRunResponse;
use App\Models\User;

/**
 * Per-item audit response. Only auditors (audits.execute) record /
 * update; anyone with audits.view can read for the dashboard.
 */
class AuditRunResponsePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('audits.view');
    }

    public function view(User $user, AuditRunResponse $response): bool
    {
        return $user->hasPermissionTo('audits.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('audits.execute');
    }

    public function update(User $user, AuditRunResponse $response): bool
    {
        return $user->hasPermissionTo('audits.execute');
    }

    public function delete(User $user, AuditRunResponse $response): bool
    {
        return $user->hasPermissionTo('audits.configure');
    }
}
