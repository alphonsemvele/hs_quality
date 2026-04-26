<?php

declare(strict_types=1);

use App\Enums\UserType;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\PermissionRegistrar;

/**
 * Block A / #55 — Mobile token issuance must be MFA-gated for privileged
 * personas (dirigeant, coordinateur, référent qualité, RH) per CDC §5.
 *
 * Web request access is gated by the RequireMfa middleware (Wave 1 / C3).
 * This test gates the parallel mobile path: even with valid credentials,
 * a sensitive role can NOT mint a Sanctum token without TOTP.
 */
beforeEach(function (): void {
    // phpunit.xml sets REQUIRE_MFA_ENROLLMENT=false globally so other test
    // suites can skip enrollment. This file tests the MFA gate itself, so
    // opt back in for every test here.
    config(['auth.require_mfa_enrollment' => true]);

    $this->seed(RoleSeeder::class);
});

function makeUser(UserType $type, ?array $extra = []): User
{
    $structure = Structure::factory()->create();
    $user = User::factory()->forStructure($structure)->state([
        'type' => $type->value,
        'password' => Hash::make('password'),
    ])->state($extra ?? [])->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->id);
    $user->assignRole($type->value);

    return $user;
}

it('lets an intervenant log in without TOTP (MFA optional for field roles)', function (): void {
    makeUser(UserType::Intervenant, ['email' => 'intervenant@x.fr']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'intervenant@x.fr',
        'password' => 'password',
    ]);

    $response->assertOk();
    expect($response->json('token'))->toBeString();
});

it('refuses dirigeant token issuance when TOTP NOT enrolled (423 mfa_enrollment_required)', function (): void {
    makeUser(UserType::Dirigeant, ['email' => 'dir@x.fr', 'two_factor_confirmed_at' => null]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'dir@x.fr',
        'password' => 'password',
    ]);

    $response->assertStatus(423);
    expect($response->json('code'))->toBe('mfa_enrollment_required');
    expect($response->json('token'))->toBeNull();
});

it('refuses coordinateur login when enrolled but no TOTP code submitted (422 mfa_code_required)', function (): void {
    makeUser(UserType::Coordinateur, [
        'email' => 'coord@x.fr',
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),  // any base32 secret for the shape check
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'coord@x.fr',
        'password' => 'password',
    ]);

    $response->assertStatus(422);
    expect($response->json('code'))->toBe('mfa_code_required');
});

it('refuses login when enrolled but TOTP code is wrong (422 mfa_code_invalid)', function (): void {
    makeUser(UserType::Dirigeant, [
        'email' => 'dir2@x.fr',
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'dir2@x.fr',
        'password' => 'password',
        'totp_code' => '000000',
    ]);

    $response->assertStatus(422);
    expect($response->json('code'))->toBe('mfa_code_invalid');
});

it('mints a token for an enrolled dirigeant with a valid TOTP code', function (): void {
    $user = makeUser(UserType::Dirigeant, [
        'email' => 'dir3@x.fr',
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);

    // Generate the current TOTP for the secret. pragmarx/google2fa is
    // deterministic — submit it back as the proof. Use the underlying
    // package class directly: Fortify's wrapper exposes only verify().
    $validCode = (new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP');

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'dir3@x.fr',
        'password' => 'password',
        'totp_code' => $validCode,
    ]);

    $response->assertOk();
    expect($response->json('token'))->toBeString();
    expect($user->fresh()->tokens()->where('name', 'mobile')->count())->toBe(1);
});

it('rejects bad credentials before MFA gate even fires', function (): void {
    makeUser(UserType::Dirigeant, ['email' => 'dir4@x.fr', 'two_factor_confirmed_at' => null]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'dir4@x.fr',
        'password' => 'wrong-password',
    ]);

    // 401 (auth) wins over 423 (mfa) — never reveal MFA state to a
    // bad-credentials caller; that would help credential-stuffers
    // enumerate which accounts have MFA on.
    $response->assertStatus(401);
});
