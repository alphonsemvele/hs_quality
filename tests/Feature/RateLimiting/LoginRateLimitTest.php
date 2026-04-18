<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Verifies rate limiters registered in AppServiceProvider fire as configured.
 * Tests the limiter directly rather than hitting routes (route wiring is
 * tested once routes are added in Phase 1 Month 1).
 */

it('registers the login limiter with 5-per-minute per IP', function () {
    $request = Request::create('/login', 'POST', ['email' => 'user@example.com']);
    $request->server->set('REMOTE_ADDR', '10.0.0.1');

    $limits = RateLimiter::limiter('login')($request);

    expect($limits)->toBeArray()
        ->and($limits)->toHaveCount(2);

    foreach ($limits as $limit) {
        expect($limit)->toBeInstanceOf(Limit::class)
            ->and($limit->maxAttempts)->toBe(5);
    }
});

it('registers the api limiter with 60-per-minute', function () {
    $request = Request::create('/api/v1/incidents', 'GET');
    $request->server->set('REMOTE_ADDR', '10.0.0.1');

    $limit = RateLimiter::limiter('api')($request);

    expect($limit)->toBeInstanceOf(Limit::class)
        ->and($limit->maxAttempts)->toBe(60);
});

it('registers the two-factor limiter at 5 per minute', function () {
    $request = Request::create('/two-factor-challenge', 'POST');
    $request->server->set('REMOTE_ADDR', '10.0.0.1');

    $limit = RateLimiter::limiter('two-factor')($request);

    expect($limit)->toBeInstanceOf(Limit::class)
        ->and($limit->maxAttempts)->toBe(5);
});

it('registers the incident-declare limiter at 30 per minute', function () {
    $request = Request::create('/api/v1/incidents', 'POST');
    $request->server->set('REMOTE_ADDR', '10.0.0.1');

    $limit = RateLimiter::limiter('incident-declare')($request);

    expect($limit)->toBeInstanceOf(Limit::class)
        ->and($limit->maxAttempts)->toBe(30);
});
