<?php

use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('lets a coordinateur create and update a care plan', function () {
    $user = actingAsRole('coordinateur');

    expect($user->can('create', CarePlan::class))->toBeTrue();

    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();

    expect($user->can('update', $plan))->toBeTrue()
        ->and($user->can('archive', $plan))->toBeTrue()
        ->and($user->can('copyTemplate', $plan))->toBeTrue();
});

it('denies a coordinateur from updating an archived plan', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->archived()->create();

    expect($user->can('update', $plan))->toBeFalse()
        ->and($user->can('archive', $plan))->toBeFalse();
});

it('denies a coordinateur from acting on another structure plan', function () {
    $user = actingAsRole('coordinateur');
    $foreignStructure = Structure::factory()->create();
    $foreignBeneficiary = Beneficiary::factory()->forStructure($foreignStructure)->create();
    $foreignPlan = CarePlan::factory()->forBeneficiary($foreignBeneficiary)->create();

    // BasePolicy::before() denies cross-tenant access.
    expect($user->can('update', $foreignPlan))->toBeFalse()
        ->and($user->can('archive', $foreignPlan))->toBeFalse();
});

it('denies an intervenant from creating a care plan', function () {
    $user = actingAsRole('intervenant');

    expect($user->can('create', CarePlan::class))->toBeFalse();
});

it('lets an intervenant view but not modify a care plan', function () {
    $user = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();

    expect($user->can('view', $plan))->toBeTrue()
        ->and($user->can('update', $plan))->toBeFalse()
        ->and($user->can('archive', $plan))->toBeFalse();
});

it('lets a dirigeant delete a care plan but not create one', function () {
    $user = actingAsRole('dirigeant');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();

    // Per the matrix: dirigeant has U (update) + D (delete) but no create.
    expect($user->can('create', CarePlan::class))->toBeFalse()
        ->and($user->can('update', $plan))->toBeTrue()
        ->and($user->can('delete', $plan))->toBeTrue();
});
