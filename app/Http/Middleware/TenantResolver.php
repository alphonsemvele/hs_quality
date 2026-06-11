<?php

namespace App\Http\Middleware;

use App\Models\Structure;
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
 * See: references/tenancy/middleware.md
 */
class TenantResolver
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        // Platform admins impersonating a tenant user: bind that user's
        // structure as the active tenant context. The platform admin's own
        // is_platform_admin flag remains true — policies still grant full
        // access, but tenant-scoped queries now target the impersonated structure.
        if ($user->is_platform_admin === true) {
            // API routes have no session middleware — hasSession() guards against
            // "Session store not set on request" on Sanctum token requests.
            $impersonating = $request->hasSession() ? $request->session()->get('impersonating_as') : null;

            if ($impersonating !== null) {
                $structure = Structure::find($impersonating['structure_id']);

                if ($structure) {
                    app()->instance('current_structure', $structure);
                    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());
                }
            }

            // Whether impersonating or not, platform admins pass through.
            // Non-impersonating admins have no tenant context — accidental
            // tenant-scoped queries return zero rows rather than leaking data.
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
