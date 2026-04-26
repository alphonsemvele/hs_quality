<?php

declare(strict_types=1);

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;

/**
 * Public registration is intentionally disabled (CDC §3 — all users provisioned
 * by their structure's dirigeant). This test ensures Fortify never re-exposes
 * /register routes after a config drift, an upgrade, or a copy/paste mistake.
 *
 * If this test fails, see config/fortify.php and confirm
 * Features::registration() remains commented out.
 */
it('does not expose GET /register', function (): void {
    $this->get('/register')->assertNotFound();
});

it('does not expose POST /register', function (): void {
    $this->post('/register', [
        'first_name' => 'Mallory',
        'last_name' => 'Attacker',
        'email' => 'mallory@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertNotFound();

    expect(User::query()->where('email', 'mallory@example.com')->exists())->toBeFalse();
});

it('does not bind the CreatesNewUsers contract', function (): void {
    expect(class_exists(CreateNewUser::class))->toBeFalse();
});
