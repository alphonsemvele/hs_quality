<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds HDS-expected security response headers on every HTTP response.
 *
 * CSP is conservative — locks down the policy Inertia + Vite + Reverb need
 * without allowing 'unsafe-inline' on scripts (a common XSS vector). When
 * adding third-party embeds (Sentry browser SDK, map providers, etc.),
 * extend the policy here and document why.
 *
 * See: references/compliance/hds-checklist.md — technical requirements
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(self), geolocation=(self), payment=()');

        // HSTS — only in production to avoid locking developers into HTTPS on localhost.
        if (app()->environment('production', 'staging')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload',
            );
        }

        // Content-Security-Policy — conservative baseline; loosen only when
        // a specific feature needs it and document the reason.
        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->buildCsp());
        }

        return $response;
    }

    private function buildCsp(): string
    {
        $appUrl = (string) config('app.url');
        $reverbHost = (string) config('reverb.servers.reverb.hostname', 'localhost');
        $reverbPort = (int) config('reverb.servers.reverb.port', 8080);
        $reverbScheme = config('reverb.servers.reverb.options.tls', false) === false ? 'ws' : 'wss';
        $reverbOrigin = "{$reverbScheme}://{$reverbHost}:{$reverbPort}";

        $parts = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-eval'",   // 'unsafe-eval' needed by some React dev tools; review in prod
            "style-src 'self' 'unsafe-inline'",   // Tailwind + Inertia inline styles; pin when feasible
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self' {$appUrl} {$reverbOrigin}",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        return implode('; ', $parts);
    }
}
