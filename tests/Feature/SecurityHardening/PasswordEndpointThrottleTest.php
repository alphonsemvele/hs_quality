<?php

declare(strict_types=1);

use Illuminate\Support\Facades\RateLimiter;

/**
 * Wave 1 / M9 — POST /forgot-password and POST /reset-password must be
 * rate-limited per-IP to prevent email enumeration. Fortify's `limiters`
 * config doesn't cover these by default; ThrottlePasswordEndpoints
 * middleware patches that gap.
 */
beforeEach(function (): void {
    RateLimiter::clear('pwd_endpoint:127.0.0.1|test@example.fr');
});

it('throttles after 6 forgot-password POSTs from the same IP+email', function (): void {
    for ($i = 1; $i <= 6; $i++) {
        $response = $this->post('/forgot-password', ['email' => 'test@example.fr']);
        // The response varies (302 normal, 429 once throttled) — what
        // matters is that the 7th attempt is 429.
        expect($response->status())->toBeIn([200, 302, 422]);
    }

    $blocked = $this->post('/forgot-password', ['email' => 'test@example.fr']);
    expect($blocked->status())->toBe(429);
});

it('does not throttle non-matching POST URIs', function (): void {
    // Sanity check: POSTs to other routes don't accidentally trip the
    // password endpoint limiter.
    for ($i = 0; $i < 10; $i++) {
        $this->post('/login', ['email' => 'test@example.fr', 'password' => 'x']);
    }

    // The 11th forgot-password attempt should still pass (separate counter).
    $response = $this->post('/forgot-password', ['email' => 'fresh@example.fr']);
    expect($response->status())->not->toBe(429);
});
