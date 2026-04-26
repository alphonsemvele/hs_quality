<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter as RL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the `password` rate limiter (6/min per IP + per email) to the
 * Fortify password-reset and 2FA-recovery endpoints.
 *
 * Wave 1 / M9. Fortify's `limiters` config only supports `login` and
 * `two-factor` out of the box; password.email / password.update / 2FA
 * recovery don't get throttled by default. Without this, an attacker can
 * enumerate emails by spamming `POST /forgot-password` with thousands of
 * addresses (200 vs 422 differential).
 *
 * Applied via the web middleware group so it runs on every Fortify POST.
 */
class ThrottlePasswordEndpoints
{
    /**
     * URI patterns that need the `password` limiter. Method-aware: only
     * write requests are throttled (GET pages can be hit freely).
     */
    private const THROTTLED_URIS = [
        'forgot-password',
        'reset-password',
        'user/two-factor-recovery-codes',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') && $this->matchesThrottledUri($request)) {
            $key = sprintf(
                'pwd_endpoint:%s|%s',
                $request->ip(),
                mb_strtolower((string) $request->input('email', '')),
            );

            if (RL::tooManyAttempts($key, maxAttempts: 6)) {
                $seconds = RL::availableIn($key);

                return response()->json(
                    ['message' => "Too many requests. Retry in {$seconds}s."],
                    429,
                );
            }

            RL::hit($key, decaySeconds: 60);
        }

        return $next($request);
    }

    private function matchesThrottledUri(Request $request): bool
    {
        $path = trim($request->path(), '/');

        foreach (self::THROTTLED_URIS as $uri) {
            if ($path === $uri) {
                return true;
            }
        }

        return false;
    }
}
