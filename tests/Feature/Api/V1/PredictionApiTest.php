<?php

declare(strict_types=1);

use App\Enums\PredictionStatus;
use App\Enums\PredictionType;
use App\Jobs\DispatchPredictionJob;
use App\Models\PredictionRequest;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Queue::fake();
});

// ── GET /api/v1/predictions ────────────────────────────────────────────────

it('returns latest prediction per type for the structure', function (): void {
    $rh = actingAsApiRole('rh');

    PredictionRequest::factory()
        ->forStructure($rh->structure)
        ->ofType(PredictionType::RisqueBurnout)
        ->completed(['score' => 0.6, 'label' => 'modéré'])
        ->create();

    $response = $this->getJson('/api/v1/predictions');

    $response->assertSuccessful();
    $response->assertJsonStructure(['data' => [
        'risque_burnout' => [],
        'perte_autonomie' => [],
        'analyse_rapport' => [],
    ]]);

    $response->assertJsonPath('data.risque_burnout.status', PredictionStatus::Termine->value);
    $response->assertJsonPath('data.perte_autonomie', null);
    $response->assertJsonPath('data.analyse_rapport', null);
});

it('returns 401 when unauthenticated', function (): void {
    $this->getJson('/api/v1/predictions')->assertUnauthorized();
});

// ── POST /api/v1/predictions ───────────────────────────────────────────────

it('creates a prediction request and dispatches the job', function (): void {
    $rh = actingAsApiRole('rh');

    $response = $this->postJson('/api/v1/predictions', [
        'type' => PredictionType::RisqueBurnout->value,
        'input_data' => ['intervenant_id' => 'abc-123'],
    ]);

    $response->assertCreated();
    $response->assertJsonPath('status', PredictionStatus::EnAttente->value);
    $response->assertJsonPath('type', PredictionType::RisqueBurnout->value);

    Queue::assertPushed(DispatchPredictionJob::class);

    expect(PredictionRequest::withoutGlobalScopes()
        ->where('structure_id', $rh->structure->id)
        ->where('type', PredictionType::RisqueBurnout->value)
        ->exists()
    )->toBeTrue();
});

it('rejects prediction request from an intervenant (403)', function (): void {
    actingAsApiRole('intervenant');

    $this->postJson('/api/v1/predictions', [
        'type' => PredictionType::RisqueBurnout->value,
        'input_data' => ['intervenant_id' => 'x'],
    ])->assertForbidden();

    Queue::assertNothingPushed();
});

it('validates that type is a known PredictionType', function (): void {
    actingAsApiRole('rh');

    $this->postJson('/api/v1/predictions', [
        'type' => 'unknown_type',
        'input_data' => [],
    ])->assertUnprocessable();
});

it('requires input_data field', function (): void {
    actingAsApiRole('rh');

    $this->postJson('/api/v1/predictions', [
        'type' => PredictionType::RisqueBurnout->value,
    ])->assertUnprocessable();
});

// ── cross-tenant isolation ─────────────────────────────────────────────────

it('index does not return predictions from another structure', function (): void {
    $rh = actingAsApiRole('rh');

    $other = Structure::factory()->create();
    PredictionRequest::factory()
        ->forStructure($other)
        ->ofType(PredictionType::RisqueBurnout)
        ->completed()
        ->create();

    $response = $this->getJson('/api/v1/predictions');

    $response->assertSuccessful();
    $response->assertJsonPath('data.risque_burnout', null);
});
