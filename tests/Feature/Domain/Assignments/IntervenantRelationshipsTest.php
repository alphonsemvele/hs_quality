<?php

use App\Models\Beneficiary;
use App\Models\IntervenantAssignment;
use App\Models\Structure;
use App\Models\User;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->intervenant = User::factory()->forStructure($this->structure)->intervenant()->create();
});

it('lists active assignments via User->assignedBeneficiaries', function () {
    $b1 = Beneficiary::factory()->forStructure($this->structure)->create();
    $b2 = Beneficiary::factory()->forStructure($this->structure)->create();

    IntervenantAssignment::factory()->forStructure($this->structure)->between($this->intervenant, $b1)->create();
    IntervenantAssignment::factory()->forStructure($this->structure)->between($this->intervenant, $b2)->create();

    expect($this->intervenant->assignedBeneficiaries)->toHaveCount(2);
});

it('excludes historical (unassigned_at != null) assignments from assignedBeneficiaries', function () {
    $b1 = Beneficiary::factory()->forStructure($this->structure)->create();
    $b2 = Beneficiary::factory()->forStructure($this->structure)->create();

    IntervenantAssignment::factory()->forStructure($this->structure)->between($this->intervenant, $b1)->create();
    IntervenantAssignment::factory()->forStructure($this->structure)->between($this->intervenant, $b2)->unassigned()->create();

    $active = $this->intervenant->assignedBeneficiaries;
    $all = $this->intervenant->allAssignedBeneficiaries;

    expect($active)->toHaveCount(1)
        ->and($active->first()->id)->toBe($b1->id)
        ->and($all)->toHaveCount(2);
});

it('lists assigned intervenants on the beneficiary side', function () {
    $beneficiary = Beneficiary::factory()->forStructure($this->structure)->create();
    $i2 = User::factory()->forStructure($this->structure)->intervenant()->create();

    IntervenantAssignment::factory()->forStructure($this->structure)->between($this->intervenant, $beneficiary)->create();
    IntervenantAssignment::factory()->forStructure($this->structure)->between($i2, $beneficiary)->create();

    expect($beneficiary->assignedIntervenants)->toHaveCount(2);
});
