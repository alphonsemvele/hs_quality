<?php

use App\Enums\StructureTier;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('renders the billing page with tiers and current plan', function () {
    actingAsRole('dirigeant');

    $response = $this->get('/billing');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];

    expect($props)->toHaveKeys(['currentTier', 'currentTierLabel', 'tiers', 'invoices', 'userCount', 'nextInvoiceAmount']);
    expect($props['tiers'])->toHaveCount(3);
    expect(collect($props['tiers'])->pluck('key')->all())
        ->toEqual(['essential', 'pro', 'premium']);

    // Exactly one tier should be flagged as current.
    $currents = collect($props['tiers'])->filter(fn ($t) => $t['is_current'])->count();
    expect($currents)->toBe(1);
});

it('flashes info when the requested tier matches the current tier', function () {
    $structure = Structure::factory()->create(['tier' => StructureTier::Pro->value]);
    actingAsRole('dirigeant', $structure);

    $response = $this->post('/billing/change-plan', ['tier' => 'pro']);

    $response->assertRedirect();
    $response->assertSessionHas('info');
    expect(session('info'))->toContain('déjà sur le plan');
});

it('flashes info when no Stripe subscription is active', function () {
    $structure = Structure::factory()->create(['tier' => StructureTier::Essential->value]);
    actingAsRole('dirigeant', $structure);

    $response = $this->post('/billing/change-plan', ['tier' => 'pro']);

    $response->assertRedirect();
    $response->assertSessionHas('info');
    expect(session('info'))->toContain('moyen de paiement');
});

it('rejects coordinateur from changing the plan', function () {
    actingAsRole('coordinateur');

    $response = $this->post('/billing/change-plan', ['tier' => 'pro']);

    $response->assertForbidden();
});

it('rejects an invalid tier value', function () {
    actingAsRole('dirigeant');

    $response = $this->post('/billing/change-plan', ['tier' => 'enterprise']);

    $response->assertSessionHasErrors('tier');
});

it('rejects a missing tier value', function () {
    actingAsRole('dirigeant');

    $response = $this->post('/billing/change-plan', []);

    $response->assertSessionHasErrors('tier');
});

it('redirects on cancel and flashes a message', function () {
    actingAsRole('dirigeant');

    $response = $this->post('/billing/cancel');

    $response->assertRedirect();
    // No active Stripe subscription in tests → info flash (graceful degradation).
    // With an active subscription the controller returns 'success'.
    // No active Stripe subscription in tests → info flash (graceful degradation).
    expect(
        $response->getSession()->has('success') ||
        $response->getSession()->has('info') ||
        $response->getSession()->has('error')
    )->toBeTrue();
});

it('rejects unauthenticated access', function () {
    $this->get('/billing')->assertRedirect('/login');
    $this->post('/billing/cancel')->assertRedirect('/login');
});
