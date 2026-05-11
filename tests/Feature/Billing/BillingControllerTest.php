<?php

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

it('redirects on change plan with info flash', function () {
    actingAsRole('dirigeant');

    $response = $this->post('/billing/change-plan', ['tier' => 'pro']);

    $response->assertRedirect();
    $response->assertSessionHas('info');
});

it('redirects on cancel with success flash', function () {
    actingAsRole('dirigeant');

    $response = $this->post('/billing/cancel');

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

it('rejects unauthenticated access', function () {
    $this->get('/billing')->assertRedirect('/login');
    $this->post('/billing/cancel')->assertRedirect('/login');
});
