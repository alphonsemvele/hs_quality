<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DataExportRequest;
use App\Models\User;

/**
 * RGPD (art. 15 / 20) self-service policy. A data-export request belongs
 * to exactly one user acting on their own portability right, so every
 * ability keys off ownership (`user_id === $user->id`) rather than a role.
 *
 * Tenant isolation is enforced one layer beneath this by
 * {@see BasePolicy::before()}, which denies any cross-structure access
 * before these methods run.
 */
class DataExportRequestPolicy extends BasePolicy
{
    /**
     * Any authenticated user may see the list — the controller filters it
     * down to their own requests by user_id.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DataExportRequest $export): bool
    {
        return $export->user_id === $user->id;
    }

    /**
     * Every authenticated user may request an export of their own data.
     */
    public function create(User $user): bool
    {
        return true;
    }

    public function download(User $user, DataExportRequest $export): bool
    {
        return $export->user_id === $user->id;
    }
}
