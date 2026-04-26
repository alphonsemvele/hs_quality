<?php

use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('issues a token on valid login', function () {
    $structure = Structure::factory()->create();
    $user = User::factory()->forStructure($structure)->create([
        'email' => 'intervenant@test.fr',
        'password' => Hash::make('secret123'),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'intervenant@test.fr',
        'password' => 'secret123',
    ])
        ->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'email', 'structure_id']]);
});

it('returns 401 on wrong password', function () {
    $structure = Structure::factory()->create();
    User::factory()->forStructure($structure)->create(['email' => 'user@test.fr']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'user@test.fr',
        'password' => 'wrong',
    ])->assertUnauthorized();
});

it('returns 401 on unknown email', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'nobody@test.fr',
        'password' => 'password',
    ])->assertUnauthorized();
});

it('returns 422 when email is missing', function () {
    $this->postJson('/api/v1/auth/login', ['password' => 'password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('returns the authenticated user on /me', function () {
    $user = actingAsApiRole('intervenant');

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('id', $user->id);
});

it('requires authentication on /me', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('deletes the token on logout', function () {
    $structure = Structure::factory()->create();
    $user = User::factory()->forStructure($structure)->create([
        'email' => 'logout@test.fr',
        'password' => Hash::make('password'),
    ]);

    // Obtain a real token via login so currentAccessToken() is populated.
    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'logout@test.fr',
        'password' => 'password',
    ])->assertOk();

    $token = $loginResponse->json('token');

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect($user->fresh()->tokens)->toBeEmpty();
});

it('revokes stale mobile tokens on fresh login', function () {
    $structure = Structure::factory()->create();
    $user = User::factory()->forStructure($structure)->create(['email' => 'multi@test.fr']);

    // Pre-existing token
    $user->createToken('mobile');

    $this->postJson('/api/v1/auth/login', [
        'email' => 'multi@test.fr',
        'password' => 'password',
    ])->assertOk();

    // Only one token should remain (the fresh one)
    expect($user->fresh()->tokens)->toHaveCount(1);
});
