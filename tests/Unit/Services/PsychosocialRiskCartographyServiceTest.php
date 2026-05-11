<?php

declare(strict_types=1);

use App\Models\QvctCampaign;
use App\Models\QvctQuestionnaire;
use App\Models\QvctResponse;
use App\Models\QvctWeakSignal;
use App\Models\Structure;
use App\Services\PsychosocialRiskCartographyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Unit coverage for PsychosocialRiskCartographyService — Phase 2
 * PHASE2_PROGRESS.md M3.15. Pins down the per-team aggregation,
 * minimum-team-size suppression (anonymity), and the active-signal
 * counts joined onto each team.
 */
beforeEach(function (): void {
    $this->service = app(PsychosocialRiskCartographyService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->questionnaire = QvctQuestionnaire::factory()
        ->forStructure($this->structure)
        ->create();

    $this->campaign = QvctCampaign::factory()->forStructure($this->structure)->create([
        'questionnaire_id' => $this->questionnaire->id,
    ]);
});

it('returns an empty cartography for a campaign with no responses', function (): void {
    expect($this->service->cartographyFor($this->campaign))->toHaveCount(0);
});

it('drops teams below MIN_TEAM_SIZE for anonymity', function (): void {
    QvctResponse::factory()->forCampaign($this->campaign)->create([
        'team_tag' => 'team_paris',
        'answers' => ['morale' => 3, 'charge' => 3, 'relations' => 3, 'isolement' => 3],
    ]);
    QvctResponse::factory()->forCampaign($this->campaign)->create([
        'team_tag' => 'team_paris',
        'answers' => ['morale' => 3, 'charge' => 3, 'relations' => 3, 'isolement' => 3],
    ]);

    // Only 2 responses for team_paris — below MIN_TEAM_SIZE = 3.
    expect($this->service->cartographyFor($this->campaign))->toHaveCount(0);
});

it('aggregates per-team mean scores when team meets MIN_TEAM_SIZE', function (): void {
    QvctResponse::factory()->forCampaign($this->campaign)->count(3)->create([
        'team_tag' => 'team_paris',
        'answers' => ['morale' => 4, 'charge' => 3, 'relations' => 5, 'isolement' => 4],
    ]);

    $cartography = $this->service->cartographyFor($this->campaign);

    expect($cartography)->toHaveCount(1);
    $team = $cartography->first();
    expect($team['team_tag'])->toBe('team_paris');
    expect($team['response_count'])->toBe(3);
    expect($team['mean_scores']['baisse_morale'])->toBe(4.0);
    expect($team['mean_scores']['surcharge'])->toBe(3.0);
});

it('joins active weak-signal counts to each team', function (): void {
    QvctResponse::factory()->forCampaign($this->campaign)->count(3)->create([
        'team_tag' => 'team_lyon',
        'answers' => ['morale' => 1, 'charge' => 5, 'relations' => 5, 'isolement' => 5],
    ]);

    QvctWeakSignal::factory()->forCampaign($this->campaign)->create([
        'team_tag' => 'team_lyon',
        'severity' => 3,
    ]);
    QvctWeakSignal::factory()->forCampaign($this->campaign)->create([
        'team_tag' => 'team_lyon',
        'severity' => 1,
        'acknowledged_at' => now(), // acknowledged → not counted as active
    ]);

    $cartography = $this->service->cartographyFor($this->campaign);

    $team = $cartography->first();
    expect($team['active_signals'])->toBe(1);
    expect($team['last_signal_severity'])->toBe(3);
});

it('handles structure-wide responses (null team_tag)', function (): void {
    QvctResponse::factory()->forCampaign($this->campaign)->count(3)->create([
        'team_tag' => null,
        'answers' => ['morale' => 2, 'charge' => 2, 'relations' => 2, 'isolement' => 2],
    ]);

    $cartography = $this->service->cartographyFor($this->campaign);

    expect($cartography)->toHaveCount(1);
    expect($cartography->first()['team_tag'])->toBeNull();
});
