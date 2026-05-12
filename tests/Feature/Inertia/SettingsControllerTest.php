<?php

use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('renders the structure settings page with identity + compliance data', function () {
    actingAsRole('dirigeant');

    $response = $this->get('/settings/structure');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];

    expect($props)->toHaveKeys(['structure', 'compliance']);
    expect($props['structure'])->toHaveKeys(['code', 'name', 'type', 'tier', 'billing_email']);
    expect($props['compliance'])->toHaveKeys([
        'hosting',
        'encryption_at_rest',
        'encryption_in_transit',
        'data_retention',
        'backup_frequency',
        'audit_log',
    ]);
});

it('updates the billing email successfully', function () {
    $user = actingAsRole('dirigeant');
    $structure = $user->structure;

    $response = $this->put('/settings/contact', [
        'billing_email' => 'nouveau-billing@demo.fr',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($structure->fresh()->billing_email)->toBe('nouveau-billing@demo.fr');
});

it('rejects an invalid billing email', function () {
    actingAsRole('dirigeant');

    $this->put('/settings/contact', [
        'billing_email' => 'not-an-email',
    ])->assertSessionHasErrors('billing_email');
});

it('rejects an empty billing email', function () {
    actingAsRole('dirigeant');

    $this->put('/settings/contact', [
        'billing_email' => '',
    ])->assertSessionHasErrors('billing_email');
});

it('rejects unauthenticated access', function () {
    $this->get('/settings/structure')->assertRedirect('/login');
    $this->put('/settings/contact', ['billing_email' => 'x@x.fr'])->assertRedirect('/login');
});
