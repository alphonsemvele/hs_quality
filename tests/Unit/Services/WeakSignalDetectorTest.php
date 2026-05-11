<?php

declare(strict_types=1);

use App\Enums\QvctWeakSignalType;
use App\Models\QvctCampaign;
use App\Models\QvctQuestionnaire;
use App\Models\QvctResponse;
use App\Models\QvctWeakSignal;
use App\Models\Structure;
use App\Services\WeakSignalDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Unit coverage for WeakSignalDetector — Phase 2 PHASE2_PROGRESS.md M3.8 +
 * M3.19. The detector is a pure scoring function over campaign responses;
 * tests pin down the threshold, sample-size guard, severity buckets, and
 * the idempotent re-detection contract.
 */
beforeEach(function (): void {
    $this->detector = app(WeakSignalDetector::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->questionnaire = QvctQuestionnaire::factory()
        ->forStructure($this->structure)
        ->create([
            'questions' => [
                ['key' => 'morale', 'label' => 'Q1', 'scale' => '1-5', 'category' => 'baisse_morale'],
                ['key' => 'charge', 'label' => 'Q2', 'scale' => '1-5', 'category' => 'surcharge'],
            ],
        ]);

    $this->campaign = QvctCampaign::factory()->forStructure($this->structure)->create([
        'questionnaire_id' => $this->questionnaire->id,
    ]);
});

function seedAnswers(QvctCampaign $campaign, array $rows): void
{
    foreach ($rows as $row) {
        QvctResponse::factory()->forCampaign($campaign)->create([
            'answers' => $row['answers'],
            'team_tag' => $row['team'] ?? null,
        ]);
    }
}

it('emits no signals when no responses exist', function (): void {
    $signals = $this->detector->detectFor($this->campaign);

    expect($signals)->toHaveCount(0);
    expect(QvctWeakSignal::count())->toBe(0);
});

it('does not emit a signal when the sample size is below the threshold', function (): void {
    seedAnswers($this->campaign, [
        ['answers' => ['morale' => 1, 'charge' => 1]],
        ['answers' => ['morale' => 1, 'charge' => 1]],
    ]);

    $signals = $this->detector->detectFor($this->campaign);

    expect($signals)->toHaveCount(0);
});

it('does not emit a signal when the mean is above threshold', function (): void {
    seedAnswers($this->campaign, [
        ['answers' => ['morale' => 4, 'charge' => 4]],
        ['answers' => ['morale' => 5, 'charge' => 4]],
        ['answers' => ['morale' => 3, 'charge' => 5]],
    ]);

    $signals = $this->detector->detectFor($this->campaign);

    expect($signals)->toHaveCount(0);
});

it('emits a baisse_morale signal when morale mean falls below threshold', function (): void {
    seedAnswers($this->campaign, [
        ['answers' => ['morale' => 1, 'charge' => 4]],
        ['answers' => ['morale' => 2, 'charge' => 4]],
        ['answers' => ['morale' => 2, 'charge' => 4]],
    ]);

    $signals = $this->detector->detectFor($this->campaign);

    expect($signals)->toHaveCount(1);
    expect($signals->first()->signal_type)->toBe(QvctWeakSignalType::BaisseMorale);
    expect($signals->first()->details['sample_size'])->toBe(3);
});

it('assigns severity 3 for a strongly negative mean (≤ 1.5)', function (): void {
    seedAnswers($this->campaign, [
        ['answers' => ['morale' => 1, 'charge' => 1]],
        ['answers' => ['morale' => 1, 'charge' => 1]],
        ['answers' => ['morale' => 2, 'charge' => 2]],
    ]);

    $signals = $this->detector->detectFor($this->campaign);

    expect($signals)->toHaveCount(2);
    expect($signals->every(fn (QvctWeakSignal $s) => $s->severity === 3))->toBeTrue();
});

it('groups signals per team_tag', function (): void {
    seedAnswers($this->campaign, [
        ['team' => 'team_paris', 'answers' => ['morale' => 1, 'charge' => 5]],
        ['team' => 'team_paris', 'answers' => ['morale' => 2, 'charge' => 5]],
        ['team' => 'team_paris', 'answers' => ['morale' => 2, 'charge' => 5]],

        ['team' => 'team_lyon', 'answers' => ['morale' => 5, 'charge' => 1]],
        ['team' => 'team_lyon', 'answers' => ['morale' => 5, 'charge' => 2]],
        ['team' => 'team_lyon', 'answers' => ['morale' => 5, 'charge' => 2]],
    ]);

    $signals = $this->detector->detectFor($this->campaign);

    expect($signals)->toHaveCount(2);
    $byTeam = $signals->groupBy('team_tag');
    expect($byTeam->get('team_paris')->first()->signal_type)->toBe(QvctWeakSignalType::BaisseMorale);
    expect($byTeam->get('team_lyon')->first()->signal_type)->toBe(QvctWeakSignalType::Surcharge);
});

it('re-running detection clears un-acknowledged signals but preserves acknowledged ones', function (): void {
    seedAnswers($this->campaign, [
        ['answers' => ['morale' => 1, 'charge' => 1]],
        ['answers' => ['morale' => 1, 'charge' => 1]],
        ['answers' => ['morale' => 1, 'charge' => 1]],
    ]);

    $first = $this->detector->detectFor($this->campaign);
    expect($first)->toHaveCount(2);

    // Acknowledge one signal — it should survive the next re-detection.
    $kept = $first->first();
    $kept->update(['acknowledged_at' => now()]);

    $second = $this->detector->detectFor($this->campaign);

    expect(QvctWeakSignal::query()->where('campaign_id', $this->campaign->id)->count())
        ->toBe($second->count() + 1); // re-emitted + the acknowledged keeper

    expect(QvctWeakSignal::query()->find($kept->id))->not->toBeNull();
});
