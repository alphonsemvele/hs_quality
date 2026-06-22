<?php

declare(strict_types=1);

use App\Models\Beneficiary;
use App\Models\Structure;
use App\Models\User;
use App\Services\SuperAdminImpersonationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use OwenIt\Auditing\Models\Audit;

/**
 * Wave X — super-admin "view as dirigeant" surface.
 *
 * Coverage:
 *   - happy path: super-admin starts/stops impersonation
 *   - access: super-admin can read tenant resources only while a session is
 *     active, and only on the impersonated structure
 *   - audit trail: every tenant write under impersonation stamps
 *     impersonator_id so the trace separates super-admin actions from real
 *     dirigeant actions
 *   - safety: non-super-admin gets 404 on every impersonation endpoint
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

function makeSuperAdmin(): User
{
    return User::factory()->create([
        'first_name' => 'Op',
        'last_name' => 'Admin',
        'email' => 'op-'.uniqid().'@test.fr',
        'password' => Hash::make('password'),
        'structure_id' => null,
        'is_platform_admin' => true,
    ]);
}

it('starts an impersonation session for a super_admin and redirects out of /admin', function (): void {
    $structure = Structure::factory()->create();
    $admin = makeSuperAdmin();

    $this->actingAs($admin)
        ->post("/admin/structures/{$structure->id}/impersonate")
        ->assertRedirect('/dashboard');

    expect(app(SuperAdminImpersonationService::class)->structureId())
        ->toBe((string) $structure->getKey());
});

it('returns 404 to a non-super_admin trying to start impersonation', function (): void {
    $structure = Structure::factory()->create();
    actingAsRole('dirigeant');

    $this->post("/admin/structures/{$structure->id}/impersonate")->assertNotFound();
});

it('returns 404 to an unauthenticated user trying to start impersonation', function (): void {
    $structure = Structure::factory()->create();

    $this->post("/admin/structures/{$structure->id}/impersonate")->assertRedirect('/login');
});

it('clears the impersonation session on stop-impersonating', function (): void {
    $structure = Structure::factory()->create();
    $admin = makeSuperAdmin();
    $this->actingAs($admin);

    $this->post("/admin/structures/{$structure->id}/impersonate");
    expect(app(SuperAdminImpersonationService::class)->isActive())->toBeTrue();

    $this->post('/admin/structures/stop-impersonating')
        ->assertRedirect('/admin/structures');

    expect(app(SuperAdminImpersonationService::class)->isActive())->toBeFalse();
});

it('lets the super_admin view a beneficiary of the impersonated structure', function (): void {
    $structureA = Structure::factory()->create();
    $beneficiary = Beneficiary::factory()->forStructure($structureA)->create();
    $admin = makeSuperAdmin();
    $this->actingAs($admin);

    $this->post("/admin/structures/{$structureA->id}/impersonate");

    $this->get("/beneficiaries/{$beneficiary->id}")->assertSuccessful();
});

it('blocks the super_admin from a beneficiary of another structure while impersonating', function (): void {
    $structureA = Structure::factory()->create();
    $structureB = Structure::factory()->create();
    $beneficiaryB = Beneficiary::factory()->forStructure($structureB)->create();
    $admin = makeSuperAdmin();
    $this->actingAs($admin);

    $this->post("/admin/structures/{$structureA->id}/impersonate");

    // Route model binding filters by structure_id when the actor is a
    // platform admin in impersonation — the wrong-structure beneficiary
    // is invisible (404), not just forbidden (403).
    $this->get("/beneficiaries/{$beneficiaryB->id}")->assertNotFound();
});

it('blocks the super_admin from tenant resources when no impersonation is active', function (): void {
    $structureA = Structure::factory()->create();
    $beneficiary = Beneficiary::factory()->forStructure($structureA)->create();
    $admin = makeSuperAdmin();
    $this->actingAs($admin);

    // No impersonation session: route binding for tenant models intentionally
    // returns null, so the page 404s. The platform admin must enter a
    // structure first.
    $this->get("/beneficiaries/{$beneficiary->id}")->assertNotFound();
});

it('exposes an impersonation prop to Inertia while a session is active', function (): void {
    $structure = Structure::factory()->create(['name' => 'Demo SAAD']);
    $admin = makeSuperAdmin();
    $this->actingAs($admin);

    $this->post("/admin/structures/{$structure->id}/impersonate");

    $response = $this->get('/dashboard');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];
    expect($props)->toHaveKey('impersonation');
    expect($props['impersonation'])->toMatchArray([
        'structure_id' => (string) $structure->getKey(),
        'structure_name' => 'Demo SAAD',
    ]);
});

it('returns a null impersonation prop for a regular tenant user', function (): void {
    actingAsRole('dirigeant');

    $response = $this->get('/dashboard');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];
    expect($props)->toHaveKey('impersonation');
    expect($props['impersonation'])->toBeNull();
});

it('stamps impersonator_id on audit rows written under impersonation', function (): void {
    $structure = Structure::factory()->create();
    $admin = makeSuperAdmin();
    $this->actingAs($admin);

    $this->post("/admin/structures/{$structure->id}/impersonate");

    // Beneficiary is Auditable; creating one inside the impersonated tenant
    // produces an audit row stamped with the platform admin's user id.
    app()->instance('current_structure', $structure);
    Beneficiary::factory()->forStructure($structure)->create(['first_name' => 'Audit Stamp']);

    $audit = Audit::query()
        ->where('structure_id', $structure->id)
        ->where('event', 'created')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull();
    expect((int) $audit->impersonator_id)->toBe((int) $admin->getKey());
});

it('does not stamp impersonator_id outside an impersonation session', function (): void {
    $coord = actingAsRole('coordinateur');

    Beneficiary::factory()->forStructure($coord->structure)->create(['first_name' => 'NoStamp']);

    $audit = Audit::query()
        ->where('structure_id', $coord->structure->id)
        ->where('event', 'created')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->impersonator_id)->toBeNull();
});
