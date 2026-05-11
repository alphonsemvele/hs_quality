<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;

/**
 * Wave 1 / C3 — TOTP enrollment is mandatory for the four privileged
 * personas (dirigeant, coordinateur, référent qualité, RH) per CDC §5.
 * RequireMfa middleware enforces this at the request boundary.
 *
 * MFA enforcement is OFF by default in tests (phpunit.xml sets
 * REQUIRE_MFA_ENROLLMENT=false). This test opts back in so the gate's
 * actual behaviour can be exercised without breaking every other test.
 */
beforeEach(function (): void {
    config(['auth.require_mfa_enrollment' => true]);
    $this->seed(RoleSeeder::class);
});

it('blocks an unenrolled dirigeant on the web with a 423 + MFA-required page', function (): void {
    actingAsRole('dirigeant');

    $response = $this->get('/dashboard');

    $response->assertStatus(423);
    $response->assertInertia(fn ($page) => $page->component('Auth/mfa-required'));
});

it('blocks an unenrolled coordinateur on the mobile API with a JSON 423', function (): void {
    actingAsApiRole('coordinateur');

    $response = $this->getJson('/api/v1/auth/me');

    $response->assertStatus(423);
    expect($response->json('code'))->toBe('mfa_enrollment_required');
});

it('lets an enrolled coordinateur through', function (): void {
    $coord = actingAsRole('coordinateur');
    $coord->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->get('/dashboard')->assertSuccessful();
});

it('lets an intervenant through (MFA optional per CDC for field roles)', function (): void {
    actingAsRole('intervenant');

    $response = $this->get('/dashboard');

    expect($response->status())->not->toBe(423);
});

it('always allows logout (so users can sign out without enrolling)', function (): void {
    actingAsRole('dirigeant');

    $this->post('/logout')->assertRedirect();
});

it('always allows the 2FA enrollment endpoints', function (): void {
    actingAsRole('dirigeant');

    // GET the QR code endpoint — exempt from RequireMfa even when not enrolled.
    $response = $this->get('/user/two-factor-qr-code');

    expect($response->status())->not->toBe(423);
});

it('passes when REQUIRE_MFA_ENROLLMENT is off (developer convenience)', function (): void {
    config(['auth.require_mfa_enrollment' => false]);
    actingAsRole('dirigeant');

    $this->get('/dashboard')->assertSuccessful();
});
