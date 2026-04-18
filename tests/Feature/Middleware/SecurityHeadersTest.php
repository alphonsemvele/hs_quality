<?php

/**
 * Verifies SecurityHeaders middleware adds the expected headers on every
 * response (HDS compliance — references/compliance/hds-checklist.md).
 */

it('adds core security headers to web responses', function () {
    $response = $this->get('/up');

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    expect(str_contains((string) $response->headers->get('Permissions-Policy'), 'camera=()'))->toBeTrue();
    expect($response->headers->get('Content-Security-Policy'))->not->toBeNull();
});

it('does not add HSTS in local environment', function () {
    $response = $this->get('/up');

    expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});

it('sets a restrictive CSP', function () {
    $response = $this->get('/up');
    $csp = (string) $response->headers->get('Content-Security-Policy');

    expect(str_contains($csp, "frame-ancestors 'none'"))->toBeTrue()
        ->and(str_contains($csp, "object-src 'none'"))->toBeTrue()
        ->and(str_contains($csp, "form-action 'self'"))->toBeTrue()
        ->and(str_contains($csp, "base-uri 'self'"))->toBeTrue();
});
