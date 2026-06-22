<?php

namespace App\Concerns;

use App\Models\Structure;
use App\Scopes\StructureScope;
use App\Support\ImpersonationSession;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant scoping trait for every domain model. Applies a global scope that
 * filters every query by the currently authenticated user's structure_id,
 * and auto-populates structure_id on create from the current tenant context.
 *
 * Mandatory on every domain model per the project's row-level tenancy rule.
 * See: references/tenancy/tenant-scoped-trait.md
 */
trait BelongsToStructure
{
    public static function bootBelongsToStructure(): void
    {
        static::addGlobalScope(new StructureScope);

        static::creating(function ($model) {
            if (! $model->structure_id && $tenant = currentStructure()) {
                $model->structure_id = $tenant->getKey();
            }
        });
    }

    /**
     * Route model binding runs inside SubstituteBindings (api group middleware),
     * which executes before auth:sanctum (route middleware) and TenantResolver.
     * At that point currentStructure() returns null, so StructureScope applies
     * "WHERE 1=0" and every binding returns 404 — even for valid resources.
     *
     * Fix: bypass StructureScope in the query, but enforce structure_id manually
     * using Sanctum's lazy user resolution. Sanctum resolves the Bearer token on
     * the first auth()->user() call anywhere in the request — even before the
     * auth:sanctum middleware has formally run — so we can filter by the real
     * user's structure_id here, preserving the 404-on-cross-tenant invariant
     * without relying on TenantResolver having already executed.
     */
    public function resolveRouteBinding($value, $field = null): ?static
    {
        $query = $this->newQueryWithoutScope(StructureScope::class)
            ->where($field ?? $this->getRouteKeyName(), $value);

        $user = auth()->user();

        if ($user !== null && ! empty($user->structure_id)) {
            $query->where($this->qualifyColumn('structure_id'), $user->structure_id);
        } elseif ($user !== null && $user->is_platform_admin === true) {
            // A platform admin reaches tenant route bindings only inside an
            // active impersonation session. Filter to the impersonated
            // structure so the 404-on-cross-tenant invariant still holds.
            // Outside an active session, the query intentionally returns null
            // so the route 404s — admins must impersonate a user first.
            $impersonating = ImpersonationSession::current();

            if ($impersonating === null) {
                return null;
            }

            $query->where($this->qualifyColumn('structure_id'), $impersonating['structure_id']);
        }

        return $query->first();
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }
}
