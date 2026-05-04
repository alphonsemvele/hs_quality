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
        // Scramble's docs UI loads Stoplight Elements from unpkg.com and is
        // already restricted to the local env by RestrictedDocsAccess, so the
        // strict CSP is skipped for docs/* to keep the dev UI usable without
        // widening the production policy.
        if (! $response->headers->has('Content-Security-Policy') && ! $request->is('docs', 'docs/*')) {
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

        $isProd = app()->environment('production', 'staging');

        // Vite dev server origins — only added in non-prod. Vite picks the
        // first available port from 5173; we whitelist 5173 + 5174 on both
        // `localhost` and `127.0.0.1`. IPv6 literals (`[::1]`) are NOT valid
        // CSP source expressions per spec, so vite.config.ts pins
        // `server.host: 'localhost'` to keep emitted URLs IPv4-shaped.
        $viteHttp = 'http://localhost:5173 http://127.0.0.1:5173 http://localhost:5174 http://127.0.0.1:5174';
        $viteWs = 'ws://localhost:5173 ws://127.0.0.1:5173 ws://localhost:5174 ws://127.0.0.1:5174';

        // Wave 1 / M4 — the dynamic-script CSP keyword defeats most XSS
        // protection CSP would otherwise give. React 19's production build
        // does not require it; only the dev runtime (vite/HMR with React
        // Refresh) does. `unsafe-inline` is also dev-only — needed by the
        // React Refresh preamble inline script emitted by @viteReactRefresh.
        $scriptSrc = $isProd
            ? "script-src 'self'"
            : "script-src 'self' 'unsafe-eval' 'unsafe-inline' {$viteHttp}";

        $connectExtras = $isProd ? '' : " {$viteHttp} {$viteWs}";

        $parts = [
            "default-src 'self'",
            $scriptSrc,
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "img-src 'self' data: blob: https://images.unsplash.com",
            "font-src 'self' data: https://fonts.bunny.net",
            "connect-src 'self' {$appUrl} {$reverbOrigin}{$connectExtras}",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        return implode('; ', $parts);
    }
}
