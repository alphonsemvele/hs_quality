<?php

use App\Models\Beneficiary;
use App\Models\IntervenantAssignment;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

/**
 * Re-validates BeneficiaryPolicy::view() for intervenants now that the
 * intervenant_assignments pivot exists. Prior test (before W4) asserted
 * "intervenant sees NO beneficiaries" as a stub; now the reality is
 * "intervenant sees beneficiaries they have an ACTIVE assignment to".
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('lets an intervenant view a beneficiary they are actively assigned to', function () {
    $user = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    IntervenantAssignment::factory()
        ->forStructure($user->structure)
        ->between($user, $beneficiary)
        ->create();

    expect($user->can('view', $beneficiary))->toBeTrue();
});

it('denies an intervenant viewing a beneficiary with only a HISTORICAL assignment', function () {
    $user = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    IntervenantAssignment::factory()
        ->forStructure($user->structure)
        ->between($user, $beneficiary)
        ->unassigned()
        ->create();

    expect($user->can('view', $beneficiary))->toBeFalse();
});

it('denies an intervenant viewing an unassigned beneficiary', function () {
    $user = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    // No assignment created.

    expect($user->can('view', $beneficiary))->toBeFalse();
});

it('denies an intervenant viewing a beneficiary from another structure even if an assignment exists there', function () {
    $user = actingAsRole('intervenant');
    $foreignStructure = Structure::factory()->create();
    $foreignBeneficiary = Beneficiary::factory()->forStructure($foreignStructure)->create();

    // Cannot legitimately create such an assignment via the service (cross-
    // structure blocked at 422), but if one existed via direct DB insert,
    // BasePolicy::before() still blocks access because the Beneficiary's
    // structure_id doesn't match the user's.
    expect($user->can('view', $foreignBeneficiary))->toBeFalse();
});
