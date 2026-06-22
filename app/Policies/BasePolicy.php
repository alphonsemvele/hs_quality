<?php

declare(strict_types=1);

namespace App\Policies;

use App\Concerns\BelongsToStructure;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Models\User;
use App\Support\ImpersonationSession;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class every domain Policy extends. Runs a tenant-ownership check
 * BEFORE any method-specific logic. If the model instance belongs to a
 * different structure than the authenticated user, access is denied —
 * defense in depth against a failed or missing global scope.
 *
 * Subclasses add role-specific checks via Spatie Permission in each
 * method (view, create, update, delete, plus any custom abilities).
 *
 * Platform-admin impersonation: when a platform admin is impersonating a
 * tenant user (see {@see ImpersonationController}),
 * they're treated as operating under the impersonated user's structure (and
 * only that structure). Cross-tenant access remains blocked even while
 * impersonation is active.
 *
 * See: references/rbac/policies-integration.md
 *      references/conventions/security-first.md
 */
abstract class BasePolicy
{
    /**
     * Runs before any other method on the Policy. Return values:
     *   - null  → continue to the specific method (normal path)
     *   - true  → allow unconditionally (super-admin pattern, rarely used here)
     *   - false → deny unconditionally
     */
    public function before(User $user, string $ability, mixed ...$args): ?bool
    {
        $model = $args[0] ?? null;

        // If the ability is being checked against a model instance, verify
        // it's in the user's tenant.
        if ($model instanceof Model && $this->modelUsesTenantScope($model)) {
            $tenantId = $model->getAttribute('structure_id');

            if ($tenantId === null) {
                return null;
            }

            $actorTenantId = $this->resolveActorStructureId($user);

            if ($actorTenantId === null || $tenantId !== $actorTenantId) {
                return false;
            }
        }

        return null;
    }

    /**
     * The structure id the user is operating under for THIS request. For
     * a regular tenant-scoped user it's their own structure_id. For a
     * platform admin impersonating a tenant user, it's the impersonated
     * user's structure — never null silently lets a platform admin operate
     * without context.
     */
    private function resolveActorStructureId(User $user): ?string
    {
        if ($user->is_platform_admin === true) {
            $payload = ImpersonationSession::current();

            return $payload !== null ? (string) $payload['structure_id'] : null;
        }

        return $user->structure_id ? (string) $user->structure_id : null;
    }

    private function modelUsesTenantScope(Model $model): bool
    {
        return in_array(
            BelongsToStructure::class,
            class_uses_recursive($model),
            true,
        );
    }
}
