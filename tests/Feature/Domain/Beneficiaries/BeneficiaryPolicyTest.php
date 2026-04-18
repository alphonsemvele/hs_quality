<?php

use App\Models\Beneficiary;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

/**
 * BeneficiaryPolicy — role-based access to beneficiary records.
 * See: references/rbac/matrix.md (M1 row)
 */

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('lets a coordinateur create a beneficiary', function () {
    $user = actingAsRole('coordinateur');

    expect($user->can('create', Beneficiary::class))->toBeTrue();
});

it('denies an intervenant from creating a beneficiary', function () {
    $user = actingAsRole('intervenant');

    expect($user->can('create', Beneficiary::class))->toBeFalse();
});

it('lets a coordinateur view any beneficiary in their structure', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    expect($user->can('view', $beneficiary))->toBeTrue();
});

it('denies a coordinateur from viewing a beneficiary from another structure', function () {
    $user = actingAsRole('coordinateur');
    $otherStructure = Structure::factory()->create();
    $foreignBeneficiary = Beneficiary::factory()->forStructure($otherStructure)->create();

    // BasePolicy::before() denies cross-tenant access regardless of role.
    expect($user->can('view', $foreignBeneficiary))->toBeFalse();
});

it('lets a dirigeant update a beneficiary', function () {
    $user = actingAsRole('dirigeant');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    expect($user->can('update', $beneficiary))->toBeTrue();
});

it('denies updating an already-erased beneficiary', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()
        ->forStructure($user->structure)
        ->create(['erased_at' => now()]);

    expect($user->can('update', $beneficiary))->toBeFalse();
});

it('only lets a dirigeant execute RGPD erasure', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiaryForCoord = Beneficiary::factory()->forStructure($coord->structure)->create();
    expect($coord->can('erase', $beneficiaryForCoord))->toBeFalse();

    $dirigeant = actingAsRole('dirigeant');
    $beneficiaryForDirigeant = Beneficiary::factory()->forStructure($dirigeant->structure)->create();
    expect($dirigeant->can('erase', $beneficiaryForDirigeant))->toBeTrue();
});

it('denies an intervenant from viewing an unassigned beneficiary', function () {
    $user = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    // Pivot lands in Phase 1 Week 4; until then intervenants see nothing
    // until they're explicitly assigned.
    expect($user->can('view', $beneficiary))->toBeFalse();
});
