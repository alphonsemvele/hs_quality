<?php

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\HandleIdempotency;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogSensitiveRead;
use App\Http\Middleware\RequireMfa;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TenantResolver;
use App\Http\Middleware\ThrottlePasswordEndpoints;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Sentry\Laravel\Integration;

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
    })->create();
