<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('renders the branded Inertia error page for a 404', function (): void {
    $this->get('/cette-route-nexiste-pas')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('Error')
            ->where('status', 404));
});

it('renders the branded Inertia error page for a 403', function (): void {
    actingAsRole('intervenant'); // lacks users.manage.structure

    $this->get('/users')
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('Error')
            ->where('status', 403));
});

it('keeps JSON / API responses machine-readable (no Inertia error page)', function (): void {
    $this->getJson('/cette-route-nexiste-pas')
        ->assertNotFound()
        ->assertHeaderMissing('X-Inertia');
});
