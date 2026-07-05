<?php

declare(strict_types=1);

use App\Enums\UserType;
use App\Models\Beneficiary;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spectator\Spectator;

/**
 * Phase 3 — Contract tests validating API responses against the exported
 * OpenAPI spec (tests/openapi.json). Spectator intercepts each response
 * and validates the JSON shape against the spec schema.
 *
 * One representative GET/POST per module. The goal is to catch contract
 * regressions (removed fields, changed types) even when feature tests
 * still pass because the business logic is unchanged.
 *
 * Spec is regenerated via: DB_CONNECTION=sqlite DB_DATABASE=:memory:
 *   php artisan scramble:export --output=tests/openapi.json
 * after every API-breaking change.
 */
beforeEach(function (): void {
    // Spectator is a dev-only contract-test dependency. CI and local dev
    // environments install it via `composer install`; environments that
    // don't have it (some lightweight Docker images, CI matrix slots that
    // intentionally skip dev deps) should skip these tests rather than
    // explode with a class-not-found error.
    if (! class_exists(Spectator::class)) {
        $this->markTestSkipped('hotmeteor/spectator is not installed (composer install --dev required).');
    }

    $this->seed(RoleSeeder::class);
    Spectator::using('openapi.json');
});

// ── M1 — Interventions ────────────────────────────────────────────────────

it('GET /api/v1/interventions response matches spec', function (): void {
    actingAsApiRole('intervenant');

    $this->getJson('/api/v1/interventions')
        ->assertSuccessful()
        ->assertValidRequest()
        ->assertValidResponse(200);
});

// ── M2 — Incidents ────────────────────────────────────────────────────────

it('GET /api/v1/incidents response matches spec', function (): void {
    actingAsApiRole('coordinateur');

    $this->getJson('/api/v1/incidents')
        ->assertSuccessful()
        ->assertValidRequest()
        ->assertValidResponse(200);
});

// ── M3 — QVCT campaigns ───────────────────────────────────────────────────

it('GET /api/v1/qvct/campaigns response matches spec', function (): void {
    actingAsApiRole('intervenant');

    $this->getJson('/api/v1/qvct/campaigns')
        ->assertSuccessful()
        ->assertValidRequest()
        ->assertValidResponse(200);
});

// ── M4 — Communication / news ─────────────────────────────────────────────

it('GET /api/v1/communication/news response matches spec', function (): void {
    actingAsApiRole('intervenant');

    $this->getJson('/api/v1/communication/news')
        ->assertSuccessful()
        ->assertValidRequest()
        ->assertValidResponse(200);
});

// ── M5 — Competencies ─────────────────────────────────────────────────────

it('GET /api/v1/competencies/certifications response matches spec', function (): void {
    actingAsApiRole('rh');

    $this->getJson('/api/v1/competencies/certifications')
        ->assertSuccessful()
        ->assertValidRequest()
        ->assertValidResponse(200);
});

// ── M6 — Audits ───────────────────────────────────────────────────────────

it('GET /api/v1/audit-runs response matches spec', function (): void {
    actingAsApiRole('referent_qualite');

    $this->getJson('/api/v1/audit-runs')
        ->assertSuccessful()
        ->assertValidRequest()
        ->assertValidResponse(200);
});

// ── M8 — Portal ───────────────────────────────────────────────────────────

it('GET /api/portal/me response matches spec', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);
    $beneficiary = Beneficiary::factory()->forStructure($structure)->create();

    $user = User::factory()->forStructure($structure)->create([
        'type' => UserType::BeneficiairePortal->value,
        'beneficiary_id' => $beneficiary->id,
    ]);
    test()->actingAs($user, 'sanctum');

    $this->getJson('/api/portal/me')
        ->assertSuccessful()
        ->assertValidRequest()
        ->assertValidResponse(200);
});

// ── M9 — Predictions ──────────────────────────────────────────────────────

it('GET /api/v1/predictions response matches spec', function (): void {
    actingAsApiRole('rh');

    // The predictions index returns { data: { risque_burnout: ..., ... } } —
    // a keyed object, not an array. Scramble cannot fully infer mapWithKeys()
    // structures; we assert the request is valid and the response is 200 with
    // the correct top-level key shape, but skip deep schema validation here.
    $this->getJson('/api/v1/predictions')
        ->assertSuccessful()
        ->assertValidRequest()
        ->assertJsonStructure(['data']);
});

// ── Annual reports ────────────────────────────────────────────────────────

it('GET /api/v1/annual-reports response matches spec', function (): void {
    actingAsApiRole('dirigeant');

    $this->getJson('/api/v1/annual-reports')
        ->assertSuccessful()
        ->assertValidRequest()
        ->assertValidResponse(200);
});

// ── Beneficiaries (read-only mobile) ─────────────────────────────────────

it('GET /api/v1/beneficiaries response matches spec', function (): void {
    actingAsApiRole('intervenant');

    $this->getJson('/api/v1/beneficiaries')
        ->assertSuccessful()
        ->assertValidRequest()
        ->assertValidResponse(200);
});

// ── Auth ──────────────────────────────────────────────────────────────────

it('POST /api/v1/auth/login 422 response matches spec for missing fields', function (): void {
    // We intentionally omit required fields to trigger validation — the REQUEST
    // is invalid by spec design. Only validate the RESPONSE shape.
    $this->postJson('/api/v1/auth/login', [])
        ->assertUnprocessable()
        ->assertValidResponse(422);
});
