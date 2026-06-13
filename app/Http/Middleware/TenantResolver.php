<?php

namespace App\Http\Middleware;

use App\Services\SuperAdminImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant (Structure) from the authenticated user and binds
 * it to the service container so:
 *   - currentStructure() helper returns it
 *   - BelongsToStructure global scope filters queries by it
 *   - Spatie Permission team_id is set so role/permission checks are tenant-scoped
 *
 * Applied on every web and API route behind auth. Public routes (landing page,
 * health check) do not need it.
 *
 * Super-admin impersonation: when the platform operator has opted into "view as
 * dirigeant" on a specific structure, this middleware binds THAT structure as
 * the tenant context instead of bypassing — letting the super-admin operate
 * the tenant surface (interventions, audits, PAC, …) without ever holding the
 * dirigeant role in a persisted form.
 *
 * See: references/tenancy/middleware.md
 */
class TenantResolver
{
    public function __construct(
        private readonly SuperAdminImpersonationService $impersonation,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        // Platform-operator accounts (is_platform_admin) deliberately have no
        // tenant. They access /admin/* routes guarded by EnsureSuperAdmin,
        // which is responsible for gating those endpoints.
        if ($user->is_platform_admin === true) {
            // Opt-in "view as dirigeant" mode: a structure has been selected
            // from /admin/structures/{id}. Resolve and bind it so the rest of
            // the request pipeline (BelongsToStructure scope, Spatie team_id,
            // BasePolicy, the React layout) behaves as if a dirigeant of that
            // structure were driving. Without the opt-in, we DO NOT bind —
            // tenant-scoped queries continue to return zero rows, preserving
            // the safe default.
            $impersonated = $this->impersonation->structure();

            if ($impersonated !== null) {
                app()->instance('current_structure', $impersonated);
                app(PermissionRegistrar::class)->setPermissionsTeamId($impersonated->getKey());
            }

            return $next($request);
        }

        if (empty($user->structure_id)) {
            abort(403, 'User is not associated with any structure.');
        }

        app()->instance('current_structure', $user->structure);

        // Bind tenant for Spatie Permission team-based role/permission scoping.
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->structure_id);

        return $next($request);
    }
}
