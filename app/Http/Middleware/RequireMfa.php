<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wave 1 / C3 — enforces TOTP enrollment for the four privileged personas
 * (dirigeant, coordinateur, référent qualité, RH) per CDC §5 mandate.
 *
 * Behavior:
 *   - Anonymous request → pass (other middleware will challenge auth).
 *   - User whose role does NOT require MFA → pass (intervenants are
 *     optional per CDC; mobile UX hinges on biometric unlock).
 *   - User who SHOULD have MFA AND has confirmed TOTP → pass.
 *   - User who SHOULD have MFA but hasn't enrolled →
 *       * web: redirect to /user/two-factor-authentication for enrollment
 *       * api: 423 Locked with a structured message
 *
 * Skip list (always allowed even without MFA):
 *   - logout (so users can sign out without enrolling)
 *   - the 2FA enrollment endpoints themselves
 *   - the 2FA challenge endpoints
 *   - password-confirmation flow (Fortify uses it during enrollment)
 *
 * To skip enforcement entirely (e.g. for local development), set
 * config('auth.require_mfa_enrollment') = false. Production MUST keep it on.
 */
class RequireMfa
{
    /**
     * URI prefixes always exempt from the gate. Listed as substrings of
     * Request::path() (no leading slash).
     */
    private const ALWAYS_EXEMPT = [
        'logout',
        'user/two-factor-authentication',
        'user/confirmed-two-factor-authentication',
        'user/two-factor-qr-code',
        'user/two-factor-recovery-codes',
        'user/two-factor-secret-key',
        'two-factor-challenge',
        'user/confirm-password',
        'user/confirmed-password-status',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('auth.require_mfa_enrollment', true)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user === null || ! $user->requiresMandatoryMfa()) {
            return $next($request);
        }

        if ($user->two_factor_confirmed_at !== null) {
            return $next($request);
        }

        if ($this->isExemptUri($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication is required for your role. '
                    .'Enroll via /user/two-factor-authentication on the web app.',
                'code' => 'mfa_enrollment_required',
            ], 423);
        }

        return Inertia::render('Auth/mfa-required', [
            'role' => $user->type instanceof \BackedEnum ? $user->type->value : (string) $user->type,
        ])->toResponse($request)->setStatusCode(423);
    }

    private function isExemptUri(Request $request): bool
    {
        $path = trim($request->path(), '/');

        foreach (self::ALWAYS_EXEMPT as $exempt) {
            if (str_contains($path, $exempt)) {
                return true;
            }
        }

        return false;
    }
}
