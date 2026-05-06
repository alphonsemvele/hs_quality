<?php

declare(strict_types=1);

use App\Enums\GraviteIncident;
use App\Enums\StatutIncident;
use App\Enums\StructureStatus;
use App\Enums\StructureTier;
use App\Models\Beneficiary;
use App\Models\Incident;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

/**
 * Platform-operator dashboard: cross-tenant KPIs + per-tenant activity.
 *
 * Coverage:
 *   - super_admin lands on /admin (or auto-redirected from /dashboard)
 *   - tenant-scoped users 404 on /admin
 *   - unauthenticated → /login
 *   - KPIs aggregate across ALL structures (no leak in either direction:
 *     the platform admin sees every tenant; tenant users never reach this surface)
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

function makePlatformAdmin(): User
{
    return User::factory()->create([
        'first_name' => 'Op',
        'last_name' => 'Admin',
        'email' => 'op'.uniqid().'@platform.fr',
        'password' => Hash::make('password'),
        'structure_id' => null,
        'is_platform_admin' => true,
        'type' => null,
    ]);
}

it('renders the platform dashboard for a super_admin', function (): void {
    $this->actingAs(makePlatformAdmin());

    Structure::factory()->count(3)->create();

    $this->get('/admin')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/dashboard/index')
            ->has('kpis')
            ->has('structures_activity')
            ->has('recent_structures')
            ->has('critical_incidents'),
        );
});

it('redirects /dashboard to /admin for a platform admin', function (): void {
    $this->actingAs(makePlatformAdmin());

    $this->get('/dashboard')->assertRedirect('/admin');
});

it('returns 404 to a tenant-scoped coordinateur on /admin', function (): void {
    actingAsRole('coordinateur');

    $this->get('/admin')->assertNotFound();
});

it('returns 404 to a dirigeant on /admin', function (): void {
    actingAsRole('dirigeant');

    $this->get('/admin')->assertNotFound();
});

it('redirects unauthenticated requests on /admin', function (): void {
    $this->get('/admin')->assertRedirect('/login');
});

it('aggregates KPIs across all tenants, not just one', function (): void {
    $admin = makePlatformAdmin();

    $structureA = Structure::factory()->create(['tier' => StructureTier::Essential->value]);
    $structureB = Structure::factory()->create(['tier' => StructureTier::Pro->value]);
    Structure::factory()->suspended()->create();

    // Two beneficiaries spread across two tenants.
    Beneficiary::factory()->forStructure($structureA)->create();
    Beneficiary::factory()->forStructure($structureB)->create();

    $this->actingAs($admin);

    $response = $this->get('/admin')->assertOk();
    $kpis = $response->viewData('page')['props']['kpis'];

    expect($kpis['total_structures'])->toBeGreaterThanOrEqual(3)
        ->and($kpis['suspended_structures'])->toBeGreaterThanOrEqual(1)
        ->and($kpis['total_beneficiaries'])->toBeGreaterThanOrEqual(2);
});

it('counts open critical incidents across all tenants', function (): void {
    $admin = makePlatformAdmin();

    $structureA = Structure::factory()->create();
    $structureB = Structure::factory()->create();

    $declarantA = User::factory()->forStructure($structureA)->state(['type' => 'intervenant'])->create();
    $declarantB = User::factory()->forStructure($structureB)->state(['type' => 'intervenant'])->create();

    Incident::factory()
        ->forStructure($structureA)
        ->declaredBy($declarantA)
        ->state([
            'gravite' => GraviteIncident::Critique->value,
            'statut' => StatutIncident::EnAnalyse->value,
        ])
        ->create();

    Incident::factory()
        ->forStructure($structureB)
        ->declaredBy($declarantB)
        ->state([
            'gravite' => GraviteIncident::Grave->value,
            'statut' => StatutIncident::Declare->value,
        ])
        ->create();

    Incident::factory()
        ->forStructure($structureA)
        ->declaredBy($declarantA)
        ->state([
            'gravite' => GraviteIncident::Critique->value,
            'statut' => StatutIncident::Clos->value, // closed — must NOT be counted
        ])
        ->create();

    $this->actingAs($admin);

    $response = $this->get('/admin')->assertOk();
    $kpis = $response->viewData('page')['props']['kpis'];

    expect($kpis['incidents_critical_open'])->toBe(2);
});

it('returns per-tenant activity rows ordered by name', function (): void {
    $admin = makePlatformAdmin();

    Structure::factory()->create(['name' => 'Beta SAAD', 'code' => 'BETA']);
    Structure::factory()->create(['name' => 'Alpha SSIAD', 'code' => 'ALPHA']);

    $this->actingAs($admin);

    $response = $this->get('/admin')->assertOk();
    $rows = $response->viewData('page')['props']['structures_activity'];

    expect($rows)->toBeArray()
        ->and(count($rows))->toBeGreaterThanOrEqual(2);

    $names = array_column($rows, 'name');
    $sorted = $names;
    sort($sorted);
    expect($names)->toBe($sorted);
});

it('does not leak any tenant rows to a non-platform user even via /admin URL forging', function (): void {
    actingAsRole('intervenant');

    // 404 — not 403 — so the surface is invisible.
    $this->get('/admin')->assertNotFound();
    $this->get('/admin/structures')->assertNotFound();
});

it('estimates MRR as a non-negative integer', function (): void {
    $admin = makePlatformAdmin();
    $structure = Structure::factory()->create([
        'tier' => StructureTier::Pro->value,
        'status' => StructureStatus::Active->value,
    ]);
    User::factory()->forStructure($structure)->state(['type' => 'intervenant'])->create();

    $this->actingAs($admin);

    $response = $this->get('/admin')->assertOk();
    $kpis = $response->viewData('page')['props']['kpis'];

    expect($kpis['mrr_estimate_eur'])->toBeInt()->toBeGreaterThanOrEqual(0);
});
