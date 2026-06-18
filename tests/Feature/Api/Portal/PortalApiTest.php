<?php

declare(strict_types=1);

use App\Enums\FamilyTokenScope;
use App\Enums\UserType;
use App\Models\Beneficiary;
use App\Models\Structure;
use App\Models\User;
use App\Services\BeneficiaryPortalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

// ── helpers ───────────────────────────────────────────────────────────────

function makePortalUser(Beneficiary $beneficiary, string $password = 'password'): User
{
    return User::factory()->forStructure($beneficiary->structure)->create([
        'type' => UserType::BeneficiairePortal->value,
        'beneficiary_id' => $beneficiary->id,
        'first_name' => $beneficiary->first_name,
        'last_name' => $beneficiary->last_name,
        'password' => Hash::make($password),
    ]);
}

// ── POST /api/portal/auth/login ───────────────────────────────────────────

it('portal login returns a sanctum token for a valid beneficiary user', function (): void {
    $structure = Structure::factory()->create();
    $beneficiary = Beneficiary::factory()->forStructure($structure)->create();
    $user = makePortalUser($beneficiary);

    $response = $this->postJson('/api/portal/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSuccessful();
    $response->assertJsonStructure(['token', 'token_type', 'user']);
});

it('portal login rejects staff users (returns 403)', function (): void {
    $structure = Structure::factory()->create();
    $staffUser = User::factory()->forStructure($structure)->create([
        'type' => UserType::Coordinateur->value,
        'password' => Hash::make('password'),
    ]);

    $this->postJson('/api/portal/auth/login', [
        'email' => $staffUser->email,
        'password' => 'password',
    ])->assertForbidden();
});

// ── GET /api/portal/me ────────────────────────────────────────────────────

it('me returns the portal user profile with beneficiary info', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);
    $beneficiary = Beneficiary::factory()->forStructure($structure)->create();
    $user = makePortalUser($beneficiary);

    $this->actingAs($user, 'sanctum');

    $this->getJson('/api/portal/me')
        ->assertSuccessful()
        ->assertJsonPath('beneficiary.id', $beneficiary->id);
});

it('staff token is rejected by portal.user middleware', function (): void {
    $rh = actingAsApiRole('rh');

    $this->getJson('/api/portal/me')->assertForbidden();
});

// ── GET /api/portal/care-plan ─────────────────────────────────────────────

it('portal care-plan returns null when beneficiary has no active plan', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);
    $beneficiary = Beneficiary::factory()->forStructure($structure)->create();
    $user = makePortalUser($beneficiary);
    $this->actingAs($user, 'sanctum');

    $this->getJson('/api/portal/care-plan')
        ->assertSuccessful()
        ->assertJsonPath('data', null);
});

// ── POST /api/portal/satisfaction ────────────────────────────────────────

it('beneficiary can submit a satisfaction rating', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);
    $beneficiary = Beneficiary::factory()->forStructure($structure)->create();
    $user = makePortalUser($beneficiary);
    $this->actingAs($user, 'sanctum');

    $this->postJson('/api/portal/satisfaction', ['score' => 4])
        ->assertCreated();
});

it('satisfaction score must be 1–5', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);
    $beneficiary = Beneficiary::factory()->forStructure($structure)->create();
    $user = makePortalUser($beneficiary);
    $this->actingAs($user, 'sanctum');

    $this->postJson('/api/portal/satisfaction', ['score' => 6])
        ->assertUnprocessable();
});

// ── Family token endpoints ────────────────────────────────────────────────

it('family member can read care plan with valid scoped token', function (): void {
    $structure = Structure::factory()->create();
    $beneficiary = Beneficiary::factory()->forStructure($structure)->create();
    $coordinator = User::factory()->forStructure($structure)->create();
    app()->instance('current_structure', $structure);

    $service = app(BeneficiaryPortalService::class);
    $plaintext = $service->issueFamilyToken(
        $beneficiary,
        $coordinator,
        'Fils aîné',
        [FamilyTokenScope::ReadPlan],
        now()->addDays(30),
    );

    $this->withToken($plaintext)
        ->getJson('/api/portal/family/care-plan')
        ->assertSuccessful();
});

it('family member is denied when scope does not include ReadPlan', function (): void {
    $structure = Structure::factory()->create();
    $beneficiary = Beneficiary::factory()->forStructure($structure)->create();
    $coordinator = User::factory()->forStructure($structure)->create();

    $service = app(BeneficiaryPortalService::class);
    $plaintext = $service->issueFamilyToken(
        $beneficiary,
        $coordinator,
        'Fille',
        [FamilyTokenScope::SubmitSatisfaction], // no ReadPlan
        now()->addDays(30),
    );

    $this->withToken($plaintext)
        ->getJson('/api/portal/family/care-plan')
        ->assertForbidden();
});

it('expired family token is rejected with 401', function (): void {
    $structure = Structure::factory()->create();
    $beneficiary = Beneficiary::factory()->forStructure($structure)->create();
    $coordinator = User::factory()->forStructure($structure)->create();

    $service = app(BeneficiaryPortalService::class);
    $plaintext = $service->issueFamilyToken(
        $beneficiary,
        $coordinator,
        'Vieux accès',
        [FamilyTokenScope::ReadPlan],
        now()->subDay(),
    );

    $this->withToken($plaintext)
        ->getJson('/api/portal/family/care-plan')
        ->assertUnauthorized();
});

it('missing family token returns 401', function (): void {
    $this->getJson('/api/portal/family/care-plan')->assertUnauthorized();
});

// ── Admin provisioning ────────────────────────────────────────────────────

it('coordinator can provision portal access for a beneficiary', function (): void {
    $coordinator = actingAsApiRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coordinator->structure)->create();

    $this->postJson("/api/v1/beneficiaries/{$beneficiary->id}/portal-access", [
        'email' => 'ben@test.com',
        'password' => 'secret12',
    ])->assertCreated()
        ->assertJsonPath('created', true);
});

it('provisioning is idempotent for the same beneficiary', function (): void {
    $coordinator = actingAsApiRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coordinator->structure)->create();

    $this->postJson("/api/v1/beneficiaries/{$beneficiary->id}/portal-access", ['email' => 'b@t.com', 'password' => 'pass1234']);
    $response = $this->postJson("/api/v1/beneficiaries/{$beneficiary->id}/portal-access", ['email' => 'b@t.com', 'password' => 'pass5678']);

    $response->assertOk()->assertJsonPath('created', false);
    expect(User::where('beneficiary_id', $beneficiary->id)->count())->toBe(1);
});
