<?php

use App\Models\Beneficiary;
use App\Models\IntervenantAssignment;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * HTTP-layer tests for AssignmentController + the assignment-aware
 * indexing in BeneficiaryController. Covers happy paths, permission
 * denials, validation, cross-tenant isolation, and the inline UI props.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// --------------------------------------------------------------------------------------
// SHOW — surfaces assignments + eligible intervenants
// --------------------------------------------------------------------------------------

it('passes active assignments + eligible intervenants to the show page', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();

    $assigned = User::factory()->forStructure($coord->structure)->intervenant()->create();
    $unassigned = User::factory()->forStructure($coord->structure)->intervenant()->create();

    IntervenantAssignment::factory()
        ->forStructure($coord->structure)
        ->between($assigned, $beneficiary)
        ->create();

    $response = $this->get("/beneficiaries/{$beneficiary->id}");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/beneficiaries/show')
            ->has('assignments.data', 1)
            ->where('assignments.data.0.intervenant.id', $assigned->id)
            ->where('assignments.data.0.is_active', true)
            ->where('can_assign', true)
            // Only the unassigned intervenant should be eligible — the
            // already-active one is filtered out.
            ->has('eligible_intervenants', 1)
            ->where('eligible_intervenants.0.id', $unassigned->id),
        );
});

it('hides the assignment UI for users without update permission (intervenant)', function () {
    $intervenant = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($intervenant->structure)->create();

    IntervenantAssignment::factory()
        ->forStructure($intervenant->structure)
        ->between($intervenant, $beneficiary)
        ->create();

    $response = $this->get("/beneficiaries/{$beneficiary->id}");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('can_assign', false)
            ->has('eligible_intervenants', 0),
        );
});

// --------------------------------------------------------------------------------------
// INDEX — intervenants see only their assigned beneficiaries
// --------------------------------------------------------------------------------------

it('limits the beneficiary index for intervenants to their actively assigned ones', function () {
    $intervenant = actingAsRole('intervenant');

    // 3 beneficiaries in the structure; only 1 assigned to this intervenant.
    [$assignedB] = Beneficiary::factory()->forStructure($intervenant->structure)->count(3)->create();

    IntervenantAssignment::factory()
        ->forStructure($intervenant->structure)
        ->between($intervenant, $assignedB)
        ->create();

    $response = $this->get('/beneficiaries');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('beneficiaries.data', 1)
            ->where('beneficiaries.data.0.id', $assignedB->id),
        );
});

it('still returns the full structure list for coordinateurs (view.structure permission)', function () {
    $coord = actingAsRole('coordinateur');
    Beneficiary::factory()->forStructure($coord->structure)->count(4)->create();

    $response = $this->get('/beneficiaries');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->has('beneficiaries.data', 4));
});

// --------------------------------------------------------------------------------------
// STORE — POST /beneficiaries/{b}/assignments
// --------------------------------------------------------------------------------------

it('attaches an intervenant to a beneficiary via POST', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->forStructure($coord->structure)->intervenant()->create();

    $response = $this->post("/beneficiaries/{$beneficiary->id}/assignments", [
        'intervenant_id' => $intervenant->id,
        'notes' => 'Visites quotidiennes après hospitalisation',
    ]);

    $response->assertRedirect();

    $assignment = IntervenantAssignment::query()->first();
    expect($assignment)->not->toBeNull()
        ->and($assignment->user_id)->toBe($intervenant->id)
        ->and($assignment->beneficiary_id)->toBe($beneficiary->id)
        ->and($assignment->assigned_by_user_id)->toBe($coord->id)
        ->and($assignment->notes)->toBe('Visites quotidiennes après hospitalisation')
        ->and($assignment->isActive())->toBeTrue();
});

it('rejects an attach request without an intervenant_id', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();

    $response = $this->post("/beneficiaries/{$beneficiary->id}/assignments", [
        'notes' => 'no intervenant supplied',
    ]);

    $response->assertSessionHasErrors('intervenant_id');
    expect(IntervenantAssignment::count())->toBe(0);
});

it('refuses to attach an intervenant from another structure (exists rule)', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $foreignStructure = Structure::factory()->create();
    $foreignIntervenant = User::factory()->forStructure($foreignStructure)->intervenant()->create();

    $response = $this->post("/beneficiaries/{$beneficiary->id}/assignments", [
        'intervenant_id' => $foreignIntervenant->id,
    ]);

    $response->assertSessionHasErrors('intervenant_id');
    expect(IntervenantAssignment::count())->toBe(0);
});

it('forbids an intervenant from attaching anyone', function () {
    $intervenant = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($intervenant->structure)->create();
    $other = User::factory()->forStructure($intervenant->structure)->intervenant()->create();

    $response = $this->post("/beneficiaries/{$beneficiary->id}/assignments", [
        'intervenant_id' => $other->id,
    ]);

    $response->assertForbidden();
    expect(IntervenantAssignment::count())->toBe(0);
});

it('returns 404 when attaching to a beneficiary in another structure', function () {
    actingAsRole('coordinateur');
    $foreignStructure = Structure::factory()->create();
    $foreignBeneficiary = Beneficiary::factory()->forStructure($foreignStructure)->create();
    $foreignIntervenant = User::factory()->forStructure($foreignStructure)->intervenant()->create();

    $response = $this->post("/beneficiaries/{$foreignBeneficiary->id}/assignments", [
        'intervenant_id' => $foreignIntervenant->id,
    ]);

    $response->assertNotFound();
});

// --------------------------------------------------------------------------------------
// DESTROY — DELETE /assignments/{assignment}
// --------------------------------------------------------------------------------------

it('unassigns via DELETE and stamps unassigned_at', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->forStructure($coord->structure)->intervenant()->create();
    $assignment = IntervenantAssignment::factory()
        ->forStructure($coord->structure)
        ->between($intervenant, $beneficiary)
        ->create();

    $response = $this->delete("/assignments/{$assignment->id}");

    $response->assertRedirect();
    expect($assignment->fresh()->unassigned_at)->not->toBeNull()
        ->and($assignment->fresh()->isActive())->toBeFalse();
});

it('forbids an intervenant from closing assignments', function () {
    $intervenant = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($intervenant->structure)->create();
    $assignment = IntervenantAssignment::factory()
        ->forStructure($intervenant->structure)
        ->between($intervenant, $beneficiary)
        ->create();

    $response = $this->delete("/assignments/{$assignment->id}");

    $response->assertForbidden();
    expect($assignment->fresh()->isActive())->toBeTrue();
});

it('returns 404 when closing an assignment from another structure', function () {
    actingAsRole('coordinateur');
    $foreignStructure = Structure::factory()->create();
    $foreignBeneficiary = Beneficiary::factory()->forStructure($foreignStructure)->create();
    $foreignIntervenant = User::factory()->forStructure($foreignStructure)->intervenant()->create();
    $foreignAssignment = IntervenantAssignment::factory()
        ->forStructure($foreignStructure)
        ->between($foreignIntervenant, $foreignBeneficiary)
        ->create();

    $response = $this->delete("/assignments/{$foreignAssignment->id}");

    // Tenant scope hides the row from route-model binding → 404 (not 403).
    $response->assertNotFound();
});
