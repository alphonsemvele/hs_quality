<?php

declare(strict_types=1);

use App\Enums\PredictionStatus;
use App\Enums\PredictionType;
use App\Models\PredictionRequest;
use App\Models\Structure;
use App\Models\User;
use App\Services\CircuitBreaker\CircuitOpenException;
use App\Services\MLPredictionService;
use App\Services\MLServiceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush(); // reset circuit breaker state between tests

    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->requester = User::factory()->forStructure($this->structure)->create();
    $this->client = $this->mock(MLServiceClient::class);
    $this->service = app(MLPredictionService::class);
});

// ── request() ────────────────────────────────────────────────────────────────

it('creates a PredictionRequest in en_attente status', function (): void {
    $prediction = $this->service->request(
        $this->structure,
        $this->requester,
        PredictionType::RisqueBurnout,
        ['intervenant_id' => 'abc-123'],
    );

    expect($prediction)->toBeInstanceOf(PredictionRequest::class)
        ->and($prediction->status)->toBe(PredictionStatus::EnAttente)
        ->and($prediction->type)->toBe(PredictionType::RisqueBurnout)
        ->and($prediction->structure_id)->toBe($this->structure->id)
        ->and($prediction->requested_by_user_id)->toBe($this->requester->id)
        ->and($prediction->ml_job_id)->toBeNull();
});

// ── process() ─────────────────────────────────────────────────────────────────

it('submits to ML service and transitions to en_traitement', function (): void {
    $this->client->shouldReceive('submit')
        ->once()
        ->withArgs(fn ($type, $payload) => $type === PredictionType::RisqueBurnout)
        ->andReturn('ml-job-xyz');

    $prediction = PredictionRequest::factory()
        ->forStructure($this->structure)
        ->ofType(PredictionType::RisqueBurnout)
        ->pending()
        ->create();

    $this->service->process($prediction);

    $prediction->refresh();
    expect($prediction->status)->toBe(PredictionStatus::EnTraitement)
        ->and($prediction->ml_job_id)->toBe('ml-job-xyz');
});

it('leaves en_attente when circuit is OPEN', function (): void {
    $this->client->shouldReceive('submit')
        ->once()
        ->andThrow(new CircuitOpenException('ml_service'));

    $prediction = PredictionRequest::factory()
        ->forStructure($this->structure)
        ->pending()
        ->create();

    $this->service->process($prediction);

    $prediction->refresh();
    expect($prediction->status)->toBe(PredictionStatus::EnAttente)
        ->and($prediction->ml_job_id)->toBeNull();
});

it('process() is a no-op for a non-pending request', function (): void {
    $this->client->shouldNotReceive('submit');

    $prediction = PredictionRequest::factory()
        ->forStructure($this->structure)
        ->processing()
        ->create();

    $this->service->process($prediction);

    $prediction->refresh();
    expect($prediction->status)->toBe(PredictionStatus::EnTraitement);
});

// ── finalise() ────────────────────────────────────────────────────────────────

it('finalises a completed result as terminé', function (): void {
    $this->client->shouldReceive('getResult')
        ->once()
        ->with('ml-job-abc')
        ->andReturn(['score' => 0.75, 'label' => 'élevé']);

    $prediction = PredictionRequest::factory()
        ->forStructure($this->structure)
        ->processing()
        ->create(['ml_job_id' => 'ml-job-abc']);

    $done = $this->service->finalise($prediction);

    $prediction->refresh();
    expect($done)->toBeTrue()
        ->and($prediction->status)->toBe(PredictionStatus::Termine)
        ->and($prediction->result['score'])->toEqual(0.75)
        ->and($prediction->completed_at)->not->toBeNull();
});

it('returns false and does not update when ML service returns null (still running)', function (): void {
    $this->client->shouldReceive('getResult')
        ->once()
        ->andReturn(null);

    $prediction = PredictionRequest::factory()
        ->forStructure($this->structure)
        ->processing()
        ->create();

    $done = $this->service->finalise($prediction);

    expect($done)->toBeFalse()
        ->and($prediction->fresh()->status)->toBe(PredictionStatus::EnTraitement);
});

it('marks as échoué when ML service throws a runtime error', function (): void {
    $this->client->shouldReceive('getResult')
        ->once()
        ->andThrow(new RuntimeException('HTTP 500 from ML service'));

    $prediction = PredictionRequest::factory()
        ->forStructure($this->structure)
        ->processing()
        ->create();

    $done = $this->service->finalise($prediction);

    $prediction->refresh();
    expect($done)->toBeTrue()
        ->and($prediction->status)->toBe(PredictionStatus::Echoue)
        ->and($prediction->error_message)->toContain('HTTP 500');
});

// ── latestFor() ───────────────────────────────────────────────────────────────

it('latestFor returns the most recent request regardless of status', function (): void {
    PredictionRequest::factory()->forStructure($this->structure)
        ->ofType(PredictionType::RisqueBurnout)
        ->completed()
        ->create(['requested_at' => now()->subDay()]);

    $newer = PredictionRequest::factory()->forStructure($this->structure)
        ->ofType(PredictionType::RisqueBurnout)
        ->pending()
        ->create(['requested_at' => now()]);

    $latest = $this->service->latestFor($this->structure, PredictionType::RisqueBurnout);

    expect($latest?->id)->toBe($newer->id);
});

it('latestFor returns null when no predictions exist for that type', function (): void {
    expect($this->service->latestFor($this->structure, PredictionType::PerteAutonomie))->toBeNull();
});

// ── cross-tenant isolation ─────────────────────────────────────────────────────

it('latestFor does not leak predictions from another structure', function (): void {
    $other = Structure::factory()->create();
    PredictionRequest::factory()->forStructure($other)
        ->ofType(PredictionType::RisqueBurnout)
        ->completed()
        ->create();

    expect($this->service->latestFor($this->structure, PredictionType::RisqueBurnout))->toBeNull();
});
