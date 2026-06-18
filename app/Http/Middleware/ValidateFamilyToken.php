<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\BeneficiaryPortalService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Validates a family member's access token from the Authorization header.
 *
 * Expects: Authorization: Bearer <plaintext-token>
 *
 * On success: binds the resolved BeneficiaryFamilyToken instance onto the
 * request as `family_token` so downstream controllers can check scope
 * without re-querying the database.
 *
 * This middleware is intentionally separate from auth:sanctum — family
 * members do not have User accounts. They are anonymous and their access
 * is scoped, time-limited, and revocable by the structure.
 */
class ValidateFamilyToken
{
    public function __construct(private readonly BeneficiaryPortalService $portalService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->bearerToken();

        if ($raw === null || $raw === '') {
            abort(401, 'Jeton d\'accès famille manquant.');
        }

        try {
            $token = $this->portalService->validateFamilyToken($raw);
        } catch (HttpException $e) {
            abort($e->getStatusCode(), $e->getMessage());
        }

        $request->attributes->set('family_token', $token);

        return $next($request);
    }
}
