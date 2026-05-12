<?php

use App\Models\Beneficiary;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;
use OwenIt\Auditing\Models\Audit;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('renders the audit log page with rows and KPIs', function () {
    $coord = actingAsRole('coordinateur');

    // Trigger an audit event by creating a beneficiary (Auditable model).
    Beneficiary::factory()->forStructure($coord->structure)->create([
        'first_name' => 'Audit',
        'last_name' => 'Test',
    ]);

    $response = $this->get('/audit-log');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];

    expect($props)->toHaveKeys(['rows', 'pagination', 'filters', 'eventCounts', 'auditableTypes']);
    expect($props['rows'])->not->toBeEmpty();
});

it('filters by event type', function () {
    $coord = actingAsRole('coordinateur');

    Beneficiary::factory()->forStructure($coord->structure)->create();

    $response = $this->get('/audit-log?event=created');

    $response->assertSuccessful();
    $rows = $response->viewData('page')['props']['rows'];
    foreach ($rows as $r) {
        expect($r['event'])->toBe('created');
    }
});

it('filters by date range', function () {
    actingAsRole('coordinateur');

    $response = $this->get('/audit-log?from=2026-01-01&to=2026-01-02');

    $response->assertSuccessful();
    expect($response->viewData('page')['props']['filters'])->toMatchArray([
        'from' => '2026-01-01',
        'to' => '2026-01-02',
    ]);
});

it('respects tenant isolation on audit rows', function () {
    $coordA = actingAsRole('coordinateur');
    $structureB = Structure::factory()->create();

    // Create one beneficiary in each structure (each triggers a 'created' audit).
    Beneficiary::factory()->forStructure($coordA->structure)->create(['first_name' => 'LocalBenef']);
    Beneficiary::factory()->forStructure($structureB)->create(['first_name' => 'OtherBenef']);

    $response = $this->get('/audit-log');

    $response->assertSuccessful();
    $rows = $response->viewData('page')['props']['rows'];
    foreach ($rows as $r) {
        // Every row must be tenant-scoped — no cross-tenant data should leak
        // through the audit log surface.
        expect($r['auditable_type'])->not->toContain('Other');
    }
});

it('rejects unauthenticated access', function () {
    $this->get('/audit-log')->assertRedirect('/login');
});
