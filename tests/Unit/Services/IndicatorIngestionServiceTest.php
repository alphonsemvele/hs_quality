<?php

declare(strict_types=1);

use App\Enums\QvctCampaignStatus;
use App\Models\QvctCampaign;
use App\Models\QvctIndicator;
use App\Models\QvctQuestionnaire;
use App\Models\QvctResponse;
use App\Models\Structure;
use App\Services\IndicatorIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-05-15 12:00:00');
    $this->service = app(IndicatorIngestionService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('snapshots a structure with no campaigns — barometer mean is null', function (): void {
    $indicator = $this->service->snapshotForStructure($this->structure);

    expect($indicator->structure_id)->toBe($this->structure->id);
    expect($indicator->period_start->toDateString())->toBe('2026-05-01');
    expect($indicator->period_end->toDateString())->toBe('2026-05-31');
    expect($indicator->barometer_mean_score)->toBeNull();
});

it('computes barometer mean from closed campaigns within the period', function (): void {
    $questionnaire = QvctQuestionnaire::factory()->forStructure($this->structure)->create();

    $campaign = QvctCampaign::factory()->forStructure($this->structure)->create([
        'questionnaire_id' => $questionnaire->id,
        'status' => QvctCampaignStatus::Closed->value,
        'closes_at' => '2026-05-10',
    ]);

    QvctResponse::factory()->forCampaign($campaign)->create([
        'answers' => ['morale' => 4, 'charge' => 3, 'relations' => 5, 'isolement' => 4],
    ]);
    QvctResponse::factory()->forCampaign($campaign)->create([
        'answers' => ['morale' => 2, 'charge' => 3, 'relations' => 4, 'isolement' => 3],
    ]);

    $indicator = $this->service->snapshotForStructure($this->structure);

    // 8 numeric answers, sum = 4+3+5+4 + 2+3+4+3 = 28; mean = 3.50
    expect((float) $indicator->barometer_mean_score)->toBe(3.50);
});

it('ignores closed campaigns outside the period', function (): void {
    $questionnaire = QvctQuestionnaire::factory()->forStructure($this->structure)->create();

    $aprilCampaign = QvctCampaign::factory()->forStructure($this->structure)->create([
        'questionnaire_id' => $questionnaire->id,
        'status' => QvctCampaignStatus::Closed->value,
        'closes_at' => '2026-04-25',
    ]);
    QvctResponse::factory()->forCampaign($aprilCampaign)->create([
        'answers' => ['morale' => 1],
    ]);

    $indicator = $this->service->snapshotForStructure($this->structure);

    expect($indicator->barometer_mean_score)->toBeNull();
});

it('is idempotent — re-running updates auto fields, preserves manual fields', function (): void {
    $first = $this->service->snapshotForStructure($this->structure);

    // Manually fill non-auto fields.
    $first->update([
        'absenteeism_rate' => 8.5,
        'turnover_rate' => 12.0,
        'work_accidents_count' => 2,
        'notes' => 'Workshop next month.',
    ]);

    $second = $this->service->snapshotForStructure($this->structure);

    expect($second->id)->toBe($first->id);
    expect((float) $second->absenteeism_rate)->toBe(8.5);
    expect((float) $second->turnover_rate)->toBe(12.0);
    expect($second->work_accidents_count)->toBe(2);
    expect($second->notes)->toBe('Workshop next month.');
});

it('snapshots all structures in one call for the cron', function (): void {
    Structure::factory()->count(2)->create();

    $count = $this->service->snapshotAllStructures();

    // 1 from beforeEach + 2 just created
    expect($count)->toBe(3);
    expect(QvctIndicator::query()->withoutGlobalScopes()->count())->toBe(3);
});
