<?php

declare(strict_types=1);

use App\Models\Beneficiary;
use App\Models\IntervenantAssignment;
use Database\Seeders\RoleSeeder;

/**
 * Wave 1 / H2 — the mobile beneficiary list endpoint must NOT return tenant
 * beneficiaries an intervenant is not assigned to.
 *
 * Earlier behavior: GET /api/v1/beneficiaries returned every beneficiary in
 * the tenant (even unassigned ones), because the controller didn't apply the
 * assigned-only scope that the web controller used.
 *
 * Fix: both controllers now delegate to BeneficiaryService::listForUser,
 * which applies the assigned-only filter when the user lacks .view.structure.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('returns ONLY assigned beneficiaries for an intervenant on mobile', function (): void {
    $intervenant = actingAsApiRole('intervenant');

    // 3 beneficiaries in this tenant; intervenant is assigned to 1.
    $assigned = Beneficiary::factory()->forStructure($intervenant->structure)->create();
    Beneficiary::factory()->forStructure($intervenant->structure)->count(2)->create();

    IntervenantAssignment::factory()
        ->forStructure($intervenant->structure)
        ->between($intervenant, $assigned)
        ->create();

    $response = $this->getJson('/api/v1/beneficiaries');

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toBe([$assigned->id]);
});

it('returns ALL tenant beneficiaries for a coordinateur on mobile', function (): void {
    $coord = actingAsApiRole('coordinateur');

    Beneficiary::factory()->forStructure($coord->structure)->count(4)->create();

    $response = $this->getJson('/api/v1/beneficiaries');

    $response->assertOk();
    expect(count($response->json('data')))->toBe(4);
});

it('returns zero beneficiaries when an intervenant has no active assignments', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    Beneficiary::factory()->forStructure($intervenant->structure)->count(3)->create();

    $response = $this->getJson('/api/v1/beneficiaries');

    $response->assertOk();
    expect($response->json('data'))->toBe([]);
});
