<?php

declare(strict_types=1);

use App\Http\Middleware\SecurityHeaders;

/**
 * Wave 1 / M4 — production CSP must NOT include the dynamic-script keyword
 * that would otherwise allow code-string execution. React 19 production
 * builds don't need it; only dev/HMR does.
 */
function buildCspFor(string $env): string
{
    $previousEnv = app('env');
    app()['env'] = $env;
    config(['app.env' => $env]);

    try {
        $middleware = new SecurityHeaders;
        $method = (new ReflectionClass($middleware))->getMethod('buildCsp');
        $method->setAccessible(true);

        return $method->invoke($middleware);
    } finally {
        app()['env'] = $previousEnv;
        config(['app.env' => $previousEnv]);
    }
}

it('omits unsafe-eval from the production CSP', function (): void {
    expect(buildCspFor('production'))->not->toContain('unsafe-eval');
});

it('omits unsafe-eval from the staging CSP', function (): void {
    expect(buildCspFor('staging'))->not->toContain('unsafe-eval');
});

it('keeps unsafe-eval in local for vite dev runtime', function (): void {
    expect(buildCspFor('local'))->toContain('unsafe-eval');
});
