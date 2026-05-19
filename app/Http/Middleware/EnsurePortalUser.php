<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route group to portal users only (UserType::BeneficiairePortal).
 *
 * Applied after auth:sanctum so the user is guaranteed to exist at this
 * point. Staff users (coordinateur, dirigeant, etc.) who somehow send
 * their admin token to a portal endpoint are denied — the portal is a
 * separate surface with different data scope.
 */
class EnsurePortalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->type !== UserType::BeneficiairePortal) {
            abort(403, 'Cet accès est réservé au portail bénéficiaires.');
        }

        return $next($request);
    }
}
