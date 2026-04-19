<?php

use App\Enums\CarePlanStatus;
use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// ----- INDEX (nested under beneficiary) -----

it('lists care plans for a beneficiary', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    CarePlan::factory()->forBeneficiary($beneficiary)->count(2)->create();

    $response = $this->get("/beneficiaries/{$beneficiary->id}/care-plans");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/care-plans/index')
            ->has('plans.data', 2)
            ->where('beneficiary.data.id', $beneficiary->id)
        );
});

it('does not leak care plans across structures in the index', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    CarePlan::factory()->forBeneficiary($beneficiary)->count(2)->create();

    $foreignStructure = Structure::factory()->create();
    $foreignBeneficiary = Beneficiary::factory()->forStructure($foreignStructure)->create();
    CarePlan::factory()->forBeneficiary($foreignBeneficiary)->count(4)->create();

    $response = $this->get("/beneficiaries/{$beneficiary->id}/care-plans");

    $response->assertInertia(fn ($page) => $page->has('plans.data', 2));
});

// ----- STORE -----

it('creates a draft plan nested under a beneficiary and records the creator', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    $response = $this->post("/beneficiaries/{$beneficiary->id}/care-plans", [
        'title' => 'Plan initial',
        'objectives' => 'Maintenir l\'autonomie',
        'start_date' => '2026-04-19',
    ]);

    $response->assertRedirect();
    $plan = CarePlan::first();

    expect($plan)->not->toBeNull()
        ->and($plan->beneficiary_id)->toBe($beneficiary->id)
        ->and($plan->structure_id)->toBe($user->structure_id)
        ->and($plan->status)->toBe(CarePlanStatus::Draft)
        ->and($plan->created_by_user_id)->toBe($user->id);
});

it('denies an intervenant from creating a plan', function () {
    $user = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    $response = $this->post("/beneficiaries/{$beneficiary->id}/care-plans", [
        'title' => 'X', 'start_date' => '2026-04-19',
    ]);

    $response->assertForbidden();
    expect(CarePlan::count())->toBe(0);
});

it('rejects a missing title', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    $response = $this->post("/beneficiaries/{$beneficiary->id}/care-plans", [
        'start_date' => '2026-04-19',
    ]);

    $response->assertSessionHasErrors('title');
});

// ----- SHOW -----

it('shows a care plan with its tasks eager-loaded', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();

    $response = $this->get("/care-plans/{$plan->id}");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/care-plans/show')
            ->where('plan.data.id', $plan->id)
            ->has('plan.data.tasks')      // nested resource collections don't get their own `data` wrapper
        );
});

it('returns 404 for a plan from another structure', function () {
    actingAsRole('coordinateur');
    $foreignStructure = Structure::factory()->create();
    $foreignBeneficiary = Beneficiary::factory()->forStructure($foreignStructure)->create();
    $foreign = CarePlan::factory()->forBeneficiary($foreignBeneficiary)->create();

    $response = $this->get("/care-plans/{$foreign->id}");

    $response->assertNotFound();
});

// ----- UPDATE -----

it('updates a plan via PUT', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create(['title' => 'Old']);

    $response = $this->put("/care-plans/{$plan->id}", [
        'title' => 'New',
        'start_date' => $plan->start_date->toDateString(),
    ]);

    $response->assertRedirect();
    expect($plan->fresh()->title)->toBe('New');
});

it('blocks updating an archived plan', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->archived()->create(['title' => 'Old']);

    $response = $this->put("/care-plans/{$plan->id}", [
        'title' => 'New',
        'start_date' => $plan->start_date->toDateString(),
    ]);

    $response->assertForbidden();
    expect($plan->fresh()->title)->toBe('Old');
});

// ----- ACTIVATE -----

it('activates a plan and auto-archives the prior active plan', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    $old = CarePlan::factory()->forBeneficiary($beneficiary)->active()->create();
    $new = CarePlan::factory()->forBeneficiary($beneficiary)->create();

    $response = $this->post("/care-plans/{$new->id}/activate");

    $response->assertRedirect();
    expect($old->fresh()->status)->toBe(CarePlanStatus::Archived)
        ->and($new->fresh()->status)->toBe(CarePlanStatus::Active);
});

// ----- ARCHIVE -----

it('archives a plan with a reason', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->active()->create();

    $response = $this->post("/care-plans/{$plan->id}/archive", [
        'reason' => 'Bénéficiaire transféré vers un autre service',
    ]);

    $response->assertRedirect();
    $fresh = $plan->fresh();
    expect($fresh->status)->toBe(CarePlanStatus::Archived)
        ->and($fresh->archived_reason)->toBe('Bénéficiaire transféré vers un autre service');
});

it('refuses to archive without a reason', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->active()->create();

    $response = $this->post("/care-plans/{$plan->id}/archive", []);

    $response->assertSessionHasErrors('reason');
});

// ----- COPY -----

it('copies a plan to another beneficiary as a draft', function () {
    $user = actingAsRole('coordinateur');
    $source = Beneficiary::factory()->forStructure($user->structure)->create();
    $target = Beneficiary::factory()->forStructure($user->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($source)->create();

    $response = $this->post("/care-plans/{$plan->id}/copy", [
        'target_beneficiary_id' => $target->id,
        'title' => 'Plan inspiré de la source',
    ]);

    $response->assertRedirect();
    expect(CarePlan::count())->toBe(2);

    $newPlan = CarePlan::where('beneficiary_id', $target->id)->first();
    expect($newPlan)->not->toBeNull()
        ->and($newPlan->status)->toBe(CarePlanStatus::Draft)
        ->and($newPlan->title)->toBe('Plan inspiré de la source');
});

it('refuses to copy to a beneficiary from another structure', function () {
    $user = actingAsRole('coordinateur');
    $source = Beneficiary::factory()->forStructure($user->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($source)->create();

    $foreignStructure = Structure::factory()->create();
    $foreignTarget = Beneficiary::factory()->forStructure($foreignStructure)->create();

    $response = $this->post("/care-plans/{$plan->id}/copy", [
        'target_beneficiary_id' => $foreignTarget->id,
        'title' => 'X',
    ]);

    // The exists: rule with structure_id scope rejects the foreign target at
    // validation time.
    $response->assertSessionHasErrors('target_beneficiary_id');
});

// ----- DESTROY -----

it('lets a dirigeant delete a plan', function () {
    $user = actingAsRole('dirigeant');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();

    $response = $this->delete("/care-plans/{$plan->id}");

    $response->assertRedirect();
    expect(CarePlan::count())->toBe(0);
    expect(CarePlan::withTrashed()->count())->toBe(1);
});

it('denies a coordinateur from deleting a plan', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();
    $plan = CarePlan::factory()->forBeneficiary($beneficiary)->create();

    $response = $this->delete("/care-plans/{$plan->id}");

    $response->assertForbidden();
    expect(CarePlan::count())->toBe(1);
});
