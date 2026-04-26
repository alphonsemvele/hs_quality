<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the platform-operator surface (/admin/structures, future
 * /admin/users for cross-tenant ops, billing, etc).
 *
 * 404 (not 403) on failure — denies even the existence of the admin
 * surface to tenant-scoped users so they can't fingerprint the routes.
 *
 * Authorization model: a single boolean flag on User (`is_platform_admin`).
 * See database/migrations/*_add_is_platform_admin_to_users.php for the
 * rationale (Spatie team-scoped roles can't represent "no tenant").
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->is_platform_admin !== true) {
            abort(404);
        }

        return $next($request);
    }
}
