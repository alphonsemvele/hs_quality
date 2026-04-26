<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;

/**
 * Wave 1 / H1 — refusing to boot when APP_ENV=production and APP_DEBUG=true.
 *
 * The guard runs in AppServiceProvider::boot. We can't easily reboot the
 * test app, so we invoke the private guard via Reflection with a temporary
 * config + environment override. The full app's normal test environment is
 * untouched.
 */
function callDebugGuard(string $env, bool $debug): void
{
    $appEnv = config('app.env');
    $appDebug = config('app.debug');

    config(['app.env' => $env, 'app.debug' => $debug]);
    app()['env'] = $env;

    try {
        $provider = new AppServiceProvider(app());
        $method = (new ReflectionClass($provider))->getMethod('guardProductionDebug');
        $method->setAccessible(true);
        $method->invoke($provider);
    } finally {
        config(['app.env' => $appEnv, 'app.debug' => $appDebug]);
        app()['env'] = $appEnv;
    }
}

it('refuses to boot when env=production and debug=true', function (): void {
    expect(fn () => callDebugGuard('production', true))
        ->toThrow(RuntimeException::class, 'Refusing to boot');
});

it('boots when env=production and debug=false', function (): void {
    expect(fn () => callDebugGuard('production', false))->not->toThrow(RuntimeException::class);
});

it('boots when env=local and debug=true (developer convenience)', function (): void {
    expect(fn () => callDebugGuard('local', true))->not->toThrow(RuntimeException::class);
});

it('boots when env=staging and debug=true (allowed for pre-prod testing)', function (): void {
    expect(fn () => callDebugGuard('staging', true))->not->toThrow(RuntimeException::class);
});
