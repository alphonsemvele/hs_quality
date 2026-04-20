<?php

use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('cannot query interventions across tenant boundaries', function () {
    $tenants = twoStructures();
    $structureA = $tenants['structureA'];
    $structureB = $tenants['structureB'];

    $intervenantA = User::factory()->create(['structure_id' => $structureA->id, 'type' => 'intervenant']);
    $beneficiaryA = Beneficiary::factory()->forStructure($structureA)->create();

    $intervenantB = User::factory()->create(['structure_id' => $structureB->id, 'type' => 'intervenant']);
    $beneficiaryB = Beneficiary::factory()->forStructure($structureB)->create();

    // Create one intervention per structure
    Intervention::factory()->forBeneficiary($beneficiaryA)->forIntervenant($intervenantA)->create();
    Intervention::factory()->forBeneficiary($beneficiaryB)->forIntervenant($intervenantB)->create();

    // Bind structure A as the current tenant
    app()->instance('current_structure', $structureA);

    // The global scope must only return structure A's intervention
    $results = Intervention::all();
    expect($results)->toHaveCount(1)
        ->and($results->first()->structure_id)->toBe($structureA->id);
});

it('returns 404 when accessing another structure intervention via route model binding', function () {
    $this->seed(RoleSeeder::class);

    $coord = actingAsRole('coordinateur');

    $otherStructure = Structure::factory()->create();
    $otherIntervenant = User::factory()->create(['structure_id' => $otherStructure->id, 'type' => 'intervenant']);
    $otherBeneficiary = Beneficiary::factory()->forStructure($otherStructure)->create();

    $foreignIntervention = Intervention::factory()
        ->forBeneficiary($otherBeneficiary)
        ->forIntervenant($otherIntervenant)
        ->create();

    $this->get("/interventions/{$foreignIntervention->id}")->assertNotFound();
    $this->put("/interventions/{$foreignIntervention->id}", ['planned_date' => '2026-06-01'])->assertNotFound();
    $this->delete("/interventions/{$foreignIntervention->id}")->assertNotFound();
});
