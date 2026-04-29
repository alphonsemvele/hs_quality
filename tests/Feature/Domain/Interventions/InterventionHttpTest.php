<?php

use App\Enums\InterventionStatus;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// ── STORE ──────────────────────────────────────────────────────────────────────

it('coordinator can plan a new intervention', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create([
        'structure_id' => $coord->structure_id,
        'type' => 'intervenant',
    ]);

    $response = $this->post('/interventions', [
        'intervenant_id' => $intervenant->id,
        'beneficiary_id' => $beneficiary->id,
        'planned_date' => '2026-05-01',
    ]);

    $response->assertRedirect();
    $intervention = Intervention::query()->first();
    expect($intervention)->not->toBeNull()
        ->and($intervention->status)->toBe(InterventionStatus::Planned)
        ->and($intervention->structure_id)->toBe($coord->structure_id);
});

it('rejects store without planned_date', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);

    $response = $this->post('/interventions', [
        'intervenant_id' => $intervenant->id,
        'beneficiary_id' => $beneficiary->id,
    ]);

    $response->assertSessionHasErrors('planned_date');
    expect(Intervention::count())->toBe(0);
});

it('rejects intervenant from another structure', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $other = Structure::factory()->create();
    $foreignIntervenant = User::factory()->create(['structure_id' => $other->id, 'type' => 'intervenant']);

    $response = $this->post('/interventions', [
        'intervenant_id' => $foreignIntervenant->id,
        'beneficiary_id' => $beneficiary->id,
        'planned_date' => '2026-05-01',
    ]);

    $response->assertSessionHasErrors('intervenant_id');
    expect(Intervention::count())->toBe(0);
});

it('forbids intervenant from creating a planned intervention via store', function () {
    $intervenant = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($intervenant->structure)->create();

    $response = $this->post('/interventions', [
        'intervenant_id' => $intervenant->id,
        'beneficiary_id' => $beneficiary->id,
        'planned_date' => '2026-05-01',
    ]);

    $response->assertForbidden();
    expect(Intervention::count())->toBe(0);
});

it('returns 404 when storing for a beneficiary in another structure', function () {
    actingAsRole('coordinateur');
    $other = Structure::factory()->create();
    $foreignBeneficiary = Beneficiary::factory()->forStructure($other)->create();
    $foreignIntervenant = User::factory()->create(['structure_id' => $other->id, 'type' => 'intervenant']);

    $response = $this->post('/interventions', [
        'intervenant_id' => $foreignIntervenant->id,
        'beneficiary_id' => $foreignBeneficiary->id,
        'planned_date' => '2026-05-01',
    ]);

    // beneficiary_id fails the exists rule scoped to current structure
    $response->assertSessionHasErrors('beneficiary_id');
});

// ── CHECKIN ────────────────────────────────────────────────────────────────────

it('coordinator can check in a planned intervention', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->planned()
        ->create();

    $response = $this->post("/interventions/{$intervention->id}/checkin");

    $response->assertRedirect();
    expect($intervention->fresh()->status)->toBe(InterventionStatus::InProgress);
});

it('forbids checkin on a non-planned intervention', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->completed()
        ->create();

    $response = $this->post("/interventions/{$intervention->id}/checkin");

    // Service throws 409 which the policy also blocks (completed → checkIn = false)
    $response->assertForbidden();
});

it('returns 404 when checking in an intervention in another structure', function () {
    actingAsRole('coordinateur');
    $other = Structure::factory()->create();
    $otherBeneficiary = Beneficiary::factory()->forStructure($other)->create();
    $otherIntervenant = User::factory()->create(['structure_id' => $other->id, 'type' => 'intervenant']);
    $foreignIntervention = Intervention::factory()
        ->forBeneficiary($otherBeneficiary)
        ->forIntervenant($otherIntervenant)
        ->planned()
        ->create();

    $response = $this->post("/interventions/{$foreignIntervention->id}/checkin");

    $response->assertNotFound();
});

// ── CHECKOUT ───────────────────────────────────────────────────────────────────

it('coordinator can check out an in-progress intervention', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->inProgress()
        ->create();

    $response = $this->post("/interventions/{$intervention->id}/checkout", [
        'report_text' => 'Visite OK.',
    ]);

    $response->assertRedirect();
    expect($intervention->fresh()->status)->toBe(InterventionStatus::Completed);
});

// ── CANCEL ─────────────────────────────────────────────────────────────────────

it('coordinator can cancel a planned intervention', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->planned()
        ->create();

    $response = $this->post("/interventions/{$intervention->id}/cancel", [
        'cancellation_reason' => 'Bénéficiaire hospitalisé.',
    ]);

    $response->assertRedirect();
    expect($intervention->fresh()->status)->toBe(InterventionStatus::Cancelled);
});

it('requires a reason to cancel', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->planned()
        ->create();

    $response = $this->post("/interventions/{$intervention->id}/cancel");

    $response->assertSessionHasErrors('cancellation_reason');
    expect($intervention->fresh()->status)->toBe(InterventionStatus::Planned);
});

// ── UPDATE ─────────────────────────────────────────────────────────────────────

it('coordinator can update a planned intervention', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->planned()
        ->create(['planned_date' => '2026-05-01']);

    $response = $this->put("/interventions/{$intervention->id}", [
        'planned_date' => '2026-05-15',
    ]);

    $response->assertRedirect();
    expect($intervention->fresh()->planned_date->format('Y-m-d'))->toBe('2026-05-15');
});

it('forbids updating a completed intervention', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->completed()
        ->create(['planned_date' => '2026-05-01']);

    $response = $this->put("/interventions/{$intervention->id}", [
        'planned_date' => '2026-06-01',
    ]);

    $response->assertForbidden();
});

// ── DESTROY ────────────────────────────────────────────────────────────────────

it('soft-deletes an intervention', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->create();

    $response = $this->delete("/interventions/{$intervention->id}");

    $response->assertRedirect();
    expect(Intervention::count())->toBe(0)
        ->and(Intervention::withTrashed()->count())->toBe(1);
});

// ── INDEX (Inertia page contract) ──────────────────────────────────────────────

it('index page returns interventions in the shape the React component expects', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create([
        'first_name' => 'Marie', 'last_name' => 'Dubois',
    ]);
    $intervenant = User::factory()->create([
        'structure_id' => $coord->structure_id,
        'first_name' => 'Pierre',
        'last_name' => 'Martin',
        'type' => 'intervenant',
    ]);
    Intervention::factory()
        ->forStructure($coord->structure)
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->state(['status' => InterventionStatus::Planned->value])
        ->create();

    $response = $this->get('/interventions');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/interventions/index')
        ->has('interventions', 1)
        ->has('interventions.0', fn ($item) => $item
            ->where('initials', 'PM')
            ->where('intervenant', 'Pierre Martin')
            ->where('beneficiaire', 'Marie Dubois')
            ->where('statut', 'planifiee')
            ->where('sync_offline', false)
            ->etc()
        )
        ->where('total', 1)
        ->has('stats', fn ($stats) => $stats
            ->where('planifiees', 1)
            ->where('en_cours', 0)
            ->where('realisees', 0)
            ->where('annulees', 0)
        )
    );
});
