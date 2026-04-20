<?php

use App\Enums\CarePlanStatus;
use App\Enums\TaskFrequency;
use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\PlannedTask;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

/**
 * HTTP-layer tests for PlannedTaskController. Covers happy paths, role
 * denials, validation, cross-tenant isolation (404s), and the
 * archived-plan freeze rule.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// --------------------------------------------------------------------------------------
// STORE — POST /care-plans/{carePlan}/tasks
// --------------------------------------------------------------------------------------

it('appends a task to a draft care plan', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();

    $response = $this->post("/care-plans/{$plan->id}/tasks", [
        'title' => 'Toilette du matin',
        'description' => 'Aide complète avec contrôle de la peau',
        'frequency' => TaskFrequency::Daily->value,
        'duration_minutes' => 30,
        'mandatory' => true,
    ]);

    $response->assertRedirect();

    $task = PlannedTask::query()->first();
    expect($task)->not->toBeNull()
        ->and($task->title)->toBe('Toilette du matin')
        ->and($task->care_plan_id)->toBe($plan->id)
        ->and($task->structure_id)->toBe($coord->structure_id)
        ->and($task->task_order)->toBe(0);
});

it('rejects a task without a title', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();

    $response = $this->post("/care-plans/{$plan->id}/tasks", [
        'frequency' => TaskFrequency::Daily->value,
    ]);

    $response->assertSessionHasErrors('title');
    expect(PlannedTask::count())->toBe(0);
});

it('forbids an intervenant from adding a task', function () {
    $intervenant = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($intervenant->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();

    $response = $this->post("/care-plans/{$plan->id}/tasks", [
        'title' => 'Should not work',
        'frequency' => TaskFrequency::Daily->value,
    ]);

    $response->assertForbidden();
    expect(PlannedTask::count())->toBe(0);
});

it('returns 404 when adding a task to a plan in another structure', function () {
    actingAsRole('coordinateur');
    $foreignStructure = Structure::factory()->create();
    $foreignBeneficiary = Beneficiary::factory()->forStructure($foreignStructure)->create();
    $foreignPlan = CarePlan::factory()->forBeneficiary($foreignBeneficiary)->create();

    $response = $this->post("/care-plans/{$foreignPlan->id}/tasks", [
        'title' => 'Cross-tenant attempt',
        'frequency' => TaskFrequency::Daily->value,
    ]);

    $response->assertNotFound();
});

// --------------------------------------------------------------------------------------
// UPDATE — PUT /tasks/{task}
// --------------------------------------------------------------------------------------

it('updates a task in a draft plan', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();
    $task = PlannedTask::factory()->forCarePlan($plan)->create([
        'title' => 'Old',
        'duration_minutes' => 15,
    ]);

    $response = $this->put("/tasks/{$task->id}", [
        'title' => 'New',
        'duration_minutes' => 45,
    ]);

    $response->assertRedirect();
    expect($task->fresh()->title)->toBe('New')
        ->and($task->fresh()->duration_minutes)->toBe(45);
});

it('forbids an intervenant from updating a task', function () {
    $intervenant = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($intervenant->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();
    $task = PlannedTask::factory()->forCarePlan($plan)->create(['title' => 'Read-only']);

    $response = $this->put("/tasks/{$task->id}", [
        'title' => 'Hacked',
    ]);

    $response->assertForbidden();
    expect($task->fresh()->title)->toBe('Read-only');
});

it('returns 404 when updating a task in another structure', function () {
    actingAsRole('coordinateur');
    $foreignStructure = Structure::factory()->create();
    $foreignBeneficiary = Beneficiary::factory()->forStructure($foreignStructure)->create();
    $foreignPlan = CarePlan::factory()->forBeneficiary($foreignBeneficiary)->create();
    $foreignTask = PlannedTask::factory()->forCarePlan($foreignPlan)->create(['title' => 'Foreign']);

    $response = $this->put("/tasks/{$foreignTask->id}", [
        'title' => 'Hacked',
    ]);

    $response->assertNotFound();
    expect($foreignTask->fresh()->title)->toBe('Foreign');
});

// --------------------------------------------------------------------------------------
// DESTROY — DELETE /tasks/{task}
// --------------------------------------------------------------------------------------

it('soft-deletes a task', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();
    $task = PlannedTask::factory()->forCarePlan($plan)->create();

    $response = $this->delete("/tasks/{$task->id}");

    $response->assertRedirect();
    expect(PlannedTask::count())->toBe(0)
        ->and(PlannedTask::withTrashed()->count())->toBe(1);
});

// --------------------------------------------------------------------------------------
// ARCHIVED PLAN — task mutations are frozen
// --------------------------------------------------------------------------------------

it('refuses to add a task to an archived plan (policy-level deny → 403)', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->archived()->create();

    $response = $this->post("/care-plans/{$plan->id}/tasks", [
        'title' => 'Should be blocked',
        'frequency' => TaskFrequency::Daily->value,
    ]);

    // The CarePlanPolicy::update returns false on archived plans,
    // and the Form Request authorizes via that → 403.
    $response->assertForbidden();
    expect(PlannedTask::count())->toBe(0);
});

it('refuses to update a task on an archived plan', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();
    $task = PlannedTask::factory()->forCarePlan($plan)->create(['title' => 'Frozen']);

    $plan->update([
        'status' => CarePlanStatus::Archived->value,
        'archived_at' => now(),
        'archived_reason' => 'Test',
    ]);

    $response = $this->put("/tasks/{$task->id}", [
        'title' => 'Mutated',
    ]);

    $response->assertForbidden();
    expect($task->fresh()->title)->toBe('Frozen');
});

it('refuses to delete a task on an archived plan', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();
    $task = PlannedTask::factory()->forCarePlan($plan)->create();

    $plan->update([
        'status' => CarePlanStatus::Archived->value,
        'archived_at' => now(),
        'archived_reason' => 'Test',
    ]);

    $response = $this->delete("/tasks/{$task->id}");

    $response->assertForbidden();
    expect(PlannedTask::count())->toBe(1);
});
