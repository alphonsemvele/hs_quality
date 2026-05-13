<?php

use App\Models\Beneficiary;
use App\Models\IntervenantAssignment;
use App\Models\Structure;
use App\Models\User;

/**
 * Mandatory cross-tenant leak test for IntervenantAssignment.
 */
beforeEach(function () {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

it('returns zero assignments when no tenant is bound', function () {
    $structure = Structure::factory()->create();
    $intervenant = User::factory()->forStructure($structure)->intervenant()->create();
    $beneficiary = Beneficiary::factory()->forStructure($structure)->create();

    IntervenantAssignment::factory()
        ->forStructure($structure)
        ->between($intervenant, $beneficiary)
        ->create();

    expect(IntervenantAssignment::count())->toBe(0);
});

it('only returns assignments from the current tenant', function () {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $interA = User::factory()->forStructure($a)->intervenant()->create();
    $interB = User::factory()->forStructure($b)->intervenant()->create();
    $benA = Beneficiary::factory()->forStructure($a)->create();
    $benB = Beneficiary::factory()->forStructure($b)->create();

    IntervenantAssignment::factory()->forStructure($a)->between($interA, $benA)->count(2)->create();
    IntervenantAssignment::factory()->forStructure($b)->between($interB, $benB)->count(3)->create();

    app()->instance('current_structure', $a);
    expect(IntervenantAssignment::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(IntervenantAssignment::count())->toBe(3);
});
