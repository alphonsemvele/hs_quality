<?php

declare(strict_types=1);

use App\Http\Resources\InertiaUserResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Wave 1 / M1 — the globally-shared Inertia auth.user prop must NOT include
 * sensitive or unnecessary User columns (email, phone, hired_at, etc).
 *
 * If you need a new field on every Inertia page, add it explicitly to
 * InertiaUserResource. Don't reach for $request->user() at the top of
 * HandleInertiaRequests::share — it leaks every fillable column.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('exposes only the slim InertiaUserResource shape', function (): void {
    $user = User::factory()->create([
        'first_name' => 'Marie',
        'last_name' => 'Durand',
        'email' => 'marie@example.fr',
        'phone' => '0612345678',
        'employee_number' => 'EMP-X',
        'hired_at' => '2020-01-15',
    ]);

    $shape = (new InertiaUserResource($user))->toArray(request());

    expect($shape)->toHaveKeys([
        'id', 'first_name', 'last_name', 'name', 'type',
        'is_platform_admin', 'requires_mfa', 'has_mfa_enrolled',
    ]);

    // These MUST NOT be exposed globally to every Inertia page.
    foreach (['email', 'phone', 'employee_number', 'hired_at', 'password', 'remember_token', 'two_factor_secret'] as $forbidden) {
        expect($shape)->not->toHaveKey($forbidden);
    }
});
