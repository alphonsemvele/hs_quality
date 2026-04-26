<?php

use App\Enums\InterventionStatus;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Event::fake();
});

// ── Index ──────────────────────────────────────────────────────────────────

it('returns paginated interventions for a coordinateur', function () {
    $coord = actingAsApiRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();

    Intervention::factory()->forBeneficiary($beneficiary)->count(3)->create();

    $this->getJson('/api/v1/interventions')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('an intervenant only sees their own interventions', function () {
    $structure = Structure::factory()->create();
    $intervenant = actingAsApiRole('intervenant', $structure);
    $other = User::factory()->forStructure($structure)->create(['type' => 'intervenant']);
    $beneficiary = Beneficiary::factory()->forStructure($structure)->create();

    Intervention::factory()->forBeneficiary($beneficiary)->forIntervenant($intervenant)->count(2)->create();
    Intervention::factory()->forBeneficiary($beneficiary)->forIntervenant($other)->count(2)->create();

    $this->getJson('/api/v1/interventions')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('requires authentication to list interventions', function () {
    $this->getJson('/api/v1/interventions')->assertUnauthorized();
});

it('does not leak interventions across structures', function () {
    $ctx = twoStructures();

    app()->instance('current_structure', $ctx['structureA']);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ctx['structureA']->getKey());
    test()->actingAs($ctx['userA'], 'sanctum');

    $benA = Beneficiary::factory()->forStructure($ctx['structureA'])->create();
    $benB = Beneficiary::factory()->forStructure($ctx['structureB'])->create();

    Intervention::factory()->forBeneficiary($benA)->count(2)->create();
    Intervention::factory()->forBeneficiary($benB)->count(3)->create();

    $this->getJson('/api/v1/interventions')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

// ── Show ───────────────────────────────────────────────────────────────────

it('returns a single intervention with beneficiary loaded', function () {
    $coord = actingAsApiRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervention = Intervention::factory()->forBeneficiary($beneficiary)->create();

    $this->getJson("/api/v1/interventions/{$intervention->id}")
        ->assertOk()
        ->assertJsonPath('id', $intervention->id)
        ->assertJsonStructure(['beneficiary' => ['id', 'first_name', 'last_name']]);
});

it('returns 404 for an intervention from another structure', function () {
    actingAsApiRole('coordinateur');

    $otherStructure = Structure::factory()->create();
    app()->instance('current_structure', $otherStructure);
    $foreignBen = Beneficiary::factory()->forStructure($otherStructure)->create();
    $foreign = Intervention::factory()->forBeneficiary($foreignBen)->create();

    $myStructure = User::find(auth()->id())->structure;
    app()->instance('current_structure', $myStructure);
    app(PermissionRegistrar::class)->setPermissionsTeamId($myStructure->getKey());

    $this->getJson("/api/v1/interventions/{$foreign->id}")->assertNotFound();
});

// ── Check-in ───────────────────────────────────────────────────────────────

it('check-in transitions a planned intervention to in_progress', function () {
    $coord = actingAsApiRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervention = Intervention::factory()->forBeneficiary($beneficiary)->planned()->create();

    $this->postJson("/api/v1/interventions/{$intervention->id}/check-in")
        ->assertOk()
        ->assertJsonPath('status', InterventionStatus::InProgress->value);
});

it('check-in accepts optional GPS coordinates', function () {
    $coord = actingAsApiRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervention = Intervention::factory()->forBeneficiary($beneficiary)->planned()->create();

    $this->postJson("/api/v1/interventions/{$intervention->id}/check-in", [
        'latitude' => '48.8566',
        'longitude' => '2.3522',
    ])->assertOk();

    expect((float) $intervention->fresh()->checkin_latitude)->toBe(48.8566);
});

it('returns 403 when checking in a non-planned intervention', function () {
    $coord = actingAsApiRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervention = Intervention::factory()->forBeneficiary($beneficiary)->inProgress()->create();

    // The policy rejects check-in when the intervention is not planned.
    $this->postJson("/api/v1/interventions/{$intervention->id}/check-in")
        ->assertForbidden();
});

// ── Check-out ──────────────────────────────────────────────────────────────

it('check-out transitions an in_progress intervention to completed', function () {
    $coord = actingAsApiRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervention = Intervention::factory()->forBeneficiary($beneficiary)->inProgress()->create();

    $this->postJson("/api/v1/interventions/{$intervention->id}/check-out", [
        'report_text' => 'Visite bien déroulée.',
    ])
        ->assertOk()
        ->assertJsonPath('status', InterventionStatus::Completed->value);
});

// ── Cancel ─────────────────────────────────────────────────────────────────

it('cancel transitions a planned intervention to cancelled', function () {
    $coord = actingAsApiRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervention = Intervention::factory()->forBeneficiary($beneficiary)->planned()->create();

    $this->postJson("/api/v1/interventions/{$intervention->id}/cancel", [
        'cancellation_reason' => 'Bénéficiaire hospitalisé.',
    ])
        ->assertOk()
        ->assertJsonPath('status', InterventionStatus::Cancelled->value);
});

// ── Idempotency ────────────────────────────────────────────────────────────

it('replays a check-in response when the same Idempotency-Key is reused', function () {
    $coord = actingAsApiRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervention = Intervention::factory()->forBeneficiary($beneficiary)->planned()->create();

    $key = (string) Str::uuid();

    $first = $this->postJson(
        "/api/v1/interventions/{$intervention->id}/check-in",
        [],
        ['Idempotency-Key' => $key],
    )->assertOk();

    // Force the intervention back to planned to prove the second request is replayed
    $intervention->update(['status' => 'planned', 'actual_start_at' => null]);

    $second = $this->postJson(
        "/api/v1/interventions/{$intervention->id}/check-in",
        [],
        ['Idempotency-Key' => $key],
    )
        ->assertOk()
        ->assertHeader('X-Idempotent-Replayed', 'true');

    expect($first->json('status'))->toBe($second->json('status'));
});
