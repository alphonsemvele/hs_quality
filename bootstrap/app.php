<?php

use App\Http\Middleware\EnsurePortalUser;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\HandleIdempotency;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogSensitiveRead;
use App\Http\Middleware\RequireMfa;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TenantResolver;
use App\Http\Middleware\ThrottlePasswordEndpoints;
use App\Http\Middleware\ValidateFamilyToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => TenantResolver::class,
            'log_sensitive_read' => LogSensitiveRead::class,
            'idempotent' => HandleIdempotency::class,
            'super_admin' => EnsureSuperAdmin::class,
            'portal.user' => EnsurePortalUser::class,
            'family.token' => ValidateFamilyToken::class,
        ]);

        // Security headers apply to every response (web, api, health check).
        // HDS expectation: no endpoint serves a response without HSTS, CSP,
        // X-Frame-Options, etc.
        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            ThrottlePasswordEndpoints::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            TenantResolver::class,
            RequireMfa::class,
        ]);

        $middleware->api(append: [
            TenantResolver::class,
            RequireMfa::class,
        ]);

        // Rate limiters configured in App\Providers\AppServiceProvider; applied
        // per-route via ->middleware('throttle:login') etc.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Forward all unhandled exceptions to Sentry when the DSN is configured.
        // Safe to call unconditionally — Sentry's integration is a no-op when
        // SENTRY_LARAVEL_DSN is empty.
        Integration::handles($exceptions);

        // Branded Inertia error pages for the web app. The mobile/API surface
        // (/api/*) and any JSON client keep machine-readable responses.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request): Response {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $response;
            }

            $status = $response->getStatusCode();

            // CSRF / session expiry: bounce back with a friendly flash rather
            // than a dead-end error screen.
            if ($status === 419) {
                return back()->with('error', 'Votre session a expiré, merci de réessayer.');
            }

            // Keep the framework's detailed debug page for server errors while
            // developing locally so stack traces remain available.
            if ($status >= 500 && app()->hasDebugModeEnabled()) {
                return $response;
            }

            if (in_array($status, [403, 404, 429, 500, 503], true)) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
