<?php

use App\Models\Beneficiary;
use App\Models\IntervenantAssignment;
use App\Models\Structure;
use App\Models\User;
use App\Services\IntervenantAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(IntervenantAssignmentService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->intervenant = User::factory()->forStructure($this->structure)->intervenant()->create();
    $this->beneficiary = Beneficiary::factory()->forStructure($this->structure)->create();
});

it('assigns an intervenant to a beneficiary in the same structure', function () {
    $coord = User::factory()->forStructure($this->structure)->coordinateur()->create();

    $assignment = $this->service->assign(
        intervenant: $this->intervenant,
        beneficiary: $this->beneficiary,
        assignedBy: $coord,
        notes: 'Regular visits after discharge',
    );

    expect($assignment)->toBeInstanceOf(IntervenantAssignment::class)
        ->and($assignment->user_id)->toBe($this->intervenant->id)
        ->and($assignment->beneficiary_id)->toBe($this->beneficiary->id)
        ->and($assignment->structure_id)->toBe($this->structure->id)
        ->and($assignment->assigned_by_user_id)->toBe($coord->id)
        ->and($assignment->notes)->toBe('Regular visits after discharge')
        ->and($assignment->isActive())->toBeTrue();
});

it('refuses to assign across structures', function () {
    $foreignStructure = Structure::factory()->create();
    $foreignBeneficiary = Beneficiary::factory()->forStructure($foreignStructure)->create();

    expect(fn () => $this->service->assign(
        intervenant: $this->intervenant,
        beneficiary: $foreignBeneficiary,
    ))->toThrow(HttpException::class);

    expect(IntervenantAssignment::count())->toBe(0);
});

it('refuses a duplicate active assignment', function () {
    $this->service->assign(intervenant: $this->intervenant, beneficiary: $this->beneficiary);

    expect(fn () => $this->service->assign(
        intervenant: $this->intervenant,
        beneficiary: $this->beneficiary,
    ))->toThrow(HttpException::class);

    expect(IntervenantAssignment::active()->count())->toBe(1);
});

it('unassigns an assignment by setting unassigned_at', function () {
    $assignment = $this->service->assign(intervenant: $this->intervenant, beneficiary: $this->beneficiary);

    $closed = $this->service->unassign($assignment, 'Intervenant leaving the structure');

    expect($closed->isActive())->toBeFalse()
        ->and($closed->unassigned_at)->not->toBeNull()
        ->and($closed->notes)->toContain('Intervenant leaving the structure');
});

it('allows re-assignment after a historical unassignment', function () {
    $first = $this->service->assign(intervenant: $this->intervenant, beneficiary: $this->beneficiary);
    $this->service->unassign($first, 'Temporary reassignment');

    $second = $this->service->assign(intervenant: $this->intervenant, beneficiary: $this->beneficiary);

    expect(IntervenantAssignment::count())->toBe(2)
        ->and(IntervenantAssignment::active()->count())->toBe(1)
        ->and($second->id)->not->toBe($first->id);
});

it('refuses to unassign an already-unassigned assignment', function () {
    $assignment = IntervenantAssignment::factory()
        ->forStructure($this->structure)
        ->between($this->intervenant, $this->beneficiary)
        ->unassigned()
        ->create();

    expect(fn () => $this->service->unassign($assignment))
        ->toThrow(HttpException::class);
});
