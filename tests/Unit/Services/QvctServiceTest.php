<?php

declare(strict_types=1);

use App\Enums\QvctCampaignStatus;
use App\Models\QvctCampaign;
use App\Models\QvctQuestionnaire;
use App\Models\QvctResponse;
use App\Models\Structure;
use App\Models\User;
use App\Services\QvctService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

/**
 * Unit coverage for QvctService — Phase 2 PHASE2_PROGRESS.md M3.7. Pins
 * down the launch / record / close lifecycle and the anonymity invariant
 * (recordResponse takes no User argument and never persists user id).
 */
beforeEach(function (): void {
    $this->service = app(QvctService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->launcher = User::factory()->forStructure($this->structure)->create();
    $this->questionnaire = QvctQuestionnaire::factory()->forStructure($this->structure)->create();
});

it('launches a campaign in active status with correct window', function (): void {
    $campaign = $this->service->launchCampaign($this->questionnaire, [
        'opens_at' => '2026-05-01',
        'closes_at' => '2026-05-15',
    ], $this->launcher);

    expect($campaign->status)->toBe(QvctCampaignStatus::Active)
        ->and($campaign->questionnaire_id)->toBe($this->questionnaire->id)
        ->and($campaign->launched_by)->toBe($this->launcher->id)
        ->and($campaign->structure_id)->toBe($this->structure->id);
});

it('refuses to launch a campaign from an archived questionnaire', function (): void {
    $this->questionnaire->update(['is_active' => false]);

    expect(fn () => $this->service->launchCampaign($this->questionnaire, [
        'opens_at' => '2026-05-01',
        'closes_at' => '2026-05-15',
    ], $this->launcher))->toThrow(HttpException::class);
});

it('records an anonymous response with no user_id stored', function (): void {
    $campaign = $this->service->launchCampaign($this->questionnaire, [
        'opens_at' => '2026-05-01',
        'closes_at' => '2026-05-15',
    ], $this->launcher);

    $response = $this->service->recordResponse(
        $campaign,
        ['morale' => 4, 'charge' => 3, 'relations' => 5, 'isolement' => 4],
        teamTag: 'team_paris',
    );

    expect($response->campaign_id)->toBe($campaign->id);
    expect($response->team_tag)->toBe('team_paris');
    expect($response->getAttributes())->not->toHaveKey('user_id');
    expect($response->answers['morale'])->toBe(4);
});

it('rejects a response submitted to a closed campaign', function (): void {
    $campaign = QvctCampaign::factory()
        ->forStructure($this->structure)
        ->state([
            'questionnaire_id' => $this->questionnaire->id,
            'status' => QvctCampaignStatus::Closed->value,
        ])
        ->create();

    expect(fn () => $this->service->recordResponse($campaign, ['morale' => 3]))
        ->toThrow(HttpException::class);
});

it('closes a campaign and runs detection (idempotent on re-close)', function (): void {
    $campaign = $this->service->launchCampaign($this->questionnaire, [
        'opens_at' => '2026-05-01',
        'closes_at' => '2026-05-15',
    ], $this->launcher);

    QvctResponse::factory()->forCampaign($campaign)->count(3)->create([
        'answers' => ['morale' => 1, 'charge' => 5, 'relations' => 5, 'isolement' => 5],
        'team_tag' => null, // Pin team to a single bucket so all 3 land in the same group.
    ]);

    $closed = $this->service->closeCampaign($campaign);
    expect($closed->status)->toBe(QvctCampaignStatus::Closed);
    expect($closed->closed_at)->not->toBeNull();
    expect($closed->weakSignals()->count())->toBeGreaterThan(0);

    $firstClosedAt = $closed->closed_at;
    $reclosed = $this->service->closeCampaign($closed);

    // Re-running detection should not bump closed_at.
    expect($reclosed->closed_at?->equalTo($firstClosedAt))->toBeTrue();
});
