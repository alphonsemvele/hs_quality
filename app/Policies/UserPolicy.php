<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserType;
use App\Models\User;

/**
 * In-tenant user management. Sister policy to StructurePolicy:
 *   - StructurePolicy gates the platform-operator surface (managing tenants).
 *   - UserPolicy gates the in-tenant surface (managing the team within a
 *     structure).
 *
 * The dirigeant of a tenant has the `users.manage.structure` permission
 * (see RoleSeeder). Coordinateurs / référents qualité / RH do NOT — they
 * can only view the user directory, not invite / deactivate.
 *
 * Cross-tenant denial is enforced by BasePolicy::before. Although User
 * does not use the BelongsToStructure trait (it IS the auth subject), the
 * `before` check still fires when a User instance is passed because
 * `structure_id !== currentUser->structure_id` would be true.
 */
class UserPolicy extends BasePolicy
{
    public function before(User $user, string $ability, mixed ...$args): ?bool
    {
        // Platform admins have no tenant; they manage tenants, not users
        // within a tenant. Block them from this surface so they can't
        // accidentally edit a tenant's people.
        if ($user->is_platform_admin === true) {
            return false;
        }

        $target = $args[0] ?? null;
        if ($target instanceof User && $target->structure_id !== $user->structure_id) {
            return false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        // Phase 1 scope: only roles with users.manage.structure (dirigeant
        // + RH per RoleSeeder) see the user directory. Phase 2 will widen
        // this for the Compétences module (coordinateur needs to see their
        // team's certifications) — at that point add a separate permission
        // rather than re-broadening this gate.
        return $user->hasPermissionTo('users.manage.structure');
    }

    public function view(User $user, User $target): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Only roles with `users.manage.structure` (dirigeant + RH per matrix)
     * can invite. Additionally we forbid inviting another super_admin or a
     * platform admin from inside a tenant — that's a platform concern.
     */
    public function invite(User $user, string $invitedRole): bool
    {
        if (! $user->hasPermissionTo('users.manage.structure')) {
            return false;
        }

        // Invitable roles are the standard 5 tenant personas. The portal
        // role + super_admin are explicitly NOT invitable from this surface.
        return in_array($invitedRole, [
            UserType::Intervenant->value,
            UserType::Coordinateur->value,
            UserType::Dirigeant->value,
            UserType::ReferentQualite->value,
            UserType::Rh->value,
        ], true);
    }

    public function update(User $user, User $target): bool
    {
        if (! $user->hasPermissionTo('users.manage.structure')) {
            return false;
        }

        // A user can always edit their own profile fields, but role/status
        // edits go through this gate. We intentionally allow self-edit at
        // the controller level via a different code path so that this gate
        // covers admin-on-other only.
        return $user->id !== $target->id;
    }

    public function deactivate(User $user, User $target): bool
    {
        if (! $this->update($user, $target)) {
            return false;
        }

        // No self-deactivate (would lock the structure out if last admin).
        return $user->id !== $target->id;
    }
}
