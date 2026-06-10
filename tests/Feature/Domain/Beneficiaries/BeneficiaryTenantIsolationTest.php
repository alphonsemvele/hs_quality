<?php

use App\Models\Beneficiary;
use App\Models\Structure;

/**
 * Mandatory cross-tenant leak test for the Beneficiary domain model.
 * See: references/tenancy/testing.md
 */
beforeEach(function () {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

it('returns zero beneficiaries when no tenant is bound', function () {
    $structure = Structure::factory()->create();
    Beneficiary::factory()->forStructure($structure)->count(3)->create();

    expect(Beneficiary::count())->toBe(0);
});

it('only returns beneficiaries from the current tenant', function () {
    $structureA = Structure::factory()->create();
    $structureB = Structure::factory()->create();

    Beneficiary::factory()->forStructure($structureA)->count(3)->create();
    Beneficiary::factory()->forStructure($structureB)->count(5)->create();

    app()->instance('current_structure', $structureA);
    expect(Beneficiary::count())->toBe(3);

    app()->instance('current_structure', $structureB);
    expect(Beneficiary::count())->toBe(5);
});

it('cannot find another tenant beneficiary by ID', function () {
    $structureA = Structure::factory()->create();
    $structureB = Structure::factory()->create();

    $foreignRecord = Beneficiary::factory()->forStructure($structureB)->create();

    app()->instance('current_structure', $structureA);
    expect(Beneficiary::find($foreignRecord->id))->toBeNull();
});

it('auto-populates structure_id from current tenant on create', function () {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);

    $beneficiary = Beneficiary::create([
        'first_name' => 'Fatima',
        'last_name' => 'NDIAYE',
        'date_of_birth' => '1942-03-15',
    ]);

    expect($beneficiary->structure_id)->toBe($structure->id);
});
