<?php

use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\PlannedTask;
use App\Models\Structure;

/**
 * Mandatory cross-tenant leak tests for CarePlan + PlannedTask.
 */

beforeEach(function () {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

// --- CarePlan ---

it('returns zero care plans when no tenant is bound', function () {
    $structure = Structure::factory()->create();
    $beneficiary = Beneficiary::factory()->forStructure($structure)->create();
    CarePlan::factory()->forBeneficiary($beneficiary)->count(3)->create();

    expect(CarePlan::count())->toBe(0);
});

it('only returns care plans from the current tenant', function () {
    $structureA = Structure::factory()->create();
    $structureB = Structure::factory()->create();

    $beneficiaryA = Beneficiary::factory()->forStructure($structureA)->create();
    $beneficiaryB = Beneficiary::factory()->forStructure($structureB)->create();

    CarePlan::factory()->forBeneficiary($beneficiaryA)->count(2)->create();
    CarePlan::factory()->forBeneficiary($beneficiaryB)->count(4)->create();

    app()->instance('current_structure', $structureA);
    expect(CarePlan::count())->toBe(2);

    app()->instance('current_structure', $structureB);
    expect(CarePlan::count())->toBe(4);
});

it('cannot find another tenant care plan by ID', function () {
    $structureA = Structure::factory()->create();
    $structureB = Structure::factory()->create();
    $beneficiaryB = Beneficiary::factory()->forStructure($structureB)->create();

    $foreign = CarePlan::factory()->forBeneficiary($beneficiaryB)->create();

    app()->instance('current_structure', $structureA);
    expect(CarePlan::find($foreign->id))->toBeNull();
});

// --- PlannedTask ---

it('only returns planned tasks from the current tenant', function () {
    $structureA = Structure::factory()->create();
    $structureB = Structure::factory()->create();

    $beneficiaryA = Beneficiary::factory()->forStructure($structureA)->create();
    $beneficiaryB = Beneficiary::factory()->forStructure($structureB)->create();

    $planA = CarePlan::factory()->forBeneficiary($beneficiaryA)->create();
    $planB = CarePlan::factory()->forBeneficiary($beneficiaryB)->create();

    PlannedTask::factory()->forCarePlan($planA)->count(3)->create();
    PlannedTask::factory()->forCarePlan($planB)->count(5)->create();

    app()->instance('current_structure', $structureA);
    expect(PlannedTask::count())->toBe(3);

    app()->instance('current_structure', $structureB);
    expect(PlannedTask::count())->toBe(5);
});

it('auto-mirrors structure_id from parent care_plan on PlannedTask create', function () {
    $structure = Structure::factory()->create();
    $beneficiary = Beneficiary::factory()->forStructure($structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();

    // Bind a different tenant to test that structure_id comes from the PARENT,
    // not from currentStructure(). Enforces the invariant documented on
    // PlannedTask::booted().
    $otherStructure = Structure::factory()->create();
    app()->instance('current_structure', $otherStructure);

    $task = PlannedTask::create([
        'care_plan_id' => $plan->id,
        'title' => 'Mandatory mirroring test',
        'frequency' => 'daily',
    ]);

    expect($task->structure_id)->toBe($plan->structure_id)
        ->and($task->structure_id)->not->toBe($otherStructure->id);
});
