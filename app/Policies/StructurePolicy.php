<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Structure;
use App\Models\User;

/**
 * Authorization for the platform-operator surface that manages tenants.
 *
 * Structure does NOT extend BasePolicy because Structure is the tenant root —
 * checking $structure->structure_id against $user->structure_id would be
 * meaningless. Instead every method gates on the User::is_platform_admin
 * flag (orthogonal to Spatie team-scoped roles — see the migration that
 * adds the flag for the why).
 *
 * Tenant users (dirigeant/coordinateur/etc) MUST NOT be able to list,
 * read, modify, or delete the Structure rows themselves — that is the
 * platform operator's surface. Modifying their own structure's settings
 * (working hours, branding, etc.) is a separate `structure.configure`
 * permission acting on a different resource.
 */
class StructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_platform_admin === true;
    }

    public function view(User $user, Structure $structure): bool
    {
        return $user->is_platform_admin === true;
    }

    public function create(User $user): bool
    {
        return $user->is_platform_admin === true;
    }

    public function update(User $user, Structure $structure): bool
    {
        return $user->is_platform_admin === true;
    }

    public function delete(User $user, Structure $structure): bool
    {
        return $user->is_platform_admin === true;
    }

    public function changeTier(User $user, Structure $structure): bool
    {
        return $user->is_platform_admin === true;
    }
}
