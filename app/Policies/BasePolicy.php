<?php

declare(strict_types=1);

namespace App\Policies;

use App\Concerns\BelongsToStructure;
use App\Models\User;
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

            if ($tenantId !== null && $tenantId !== $user->structure_id) {
                return false;
            }
        }

        return null;
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
