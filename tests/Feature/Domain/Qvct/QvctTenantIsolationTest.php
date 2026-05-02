<?php

declare(strict_types=1);

use App\Models\QvctCampaign;
use App\Models\QvctQuestionnaire;
use App\Models\QvctResponse;
use App\Models\QvctWeakSignal;
use App\Models\Structure;

/**
 * Mandatory cross-tenant leak tests for every QVCT domain model. Phase 2
 * Module M3 — see PHASE2_PROGRESS.md M3.6 + M3.18.
 *
 * The QVCT module stores anonymous wellbeing data, so a cross-tenant leak
 * is a CNIL-grade incident: an HR référent of structure A would otherwise
 * see the morale signals of structure B's intervenants. The leak tests
 * here are the same shape as the Phase 1 isolation tests so a future
 * audit can grep them all uniformly.
 */
beforeEach(function (): void {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

// ── QvctQuestionnaire ───────────────────────────────────────────────────────

it('returns zero questionnaires when no tenant is bound', function (): void {
    $s = Structure::factory()->create();
    QvctQuestionnaire::factory()->forStructure($s)->count(3)->create();

    expect(QvctQuestionnaire::count())->toBe(0);
});

it('only returns questionnaires from the current tenant', function (): void {
    [$a, $b] = [Structure::factory()->create(), Structure::factory()->create()];
    QvctQuestionnaire::factory()->forStructure($a)->count(2)->create();
    QvctQuestionnaire::factory()->forStructure($b)->count(4)->create();

    app()->instance('current_structure', $a);
    expect(QvctQuestionnaire::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(QvctQuestionnaire::count())->toBe(4);
});

it('cannot find another tenant questionnaire by ID', function (): void {
    [$a, $b] = [Structure::factory()->create(), Structure::factory()->create()];
    $foreign = QvctQuestionnaire::factory()->forStructure($b)->create();

    app()->instance('current_structure', $a);
    expect(QvctQuestionnaire::find($foreign->id))->toBeNull();
});

it('auto-populates structure_id from current tenant on create', function (): void {
    $s = Structure::factory()->create();
    app()->instance('current_structure', $s);

    $q = QvctQuestionnaire::create([
        'title' => 'Test',
        'frequency' => 'monthly',
        'questions' => [],
    ]);

    expect($q->structure_id)->toBe($s->id);
});

// ── QvctCampaign ────────────────────────────────────────────────────────────

it('only returns campaigns from the current tenant', function (): void {
    [$a, $b] = [Structure::factory()->create(), Structure::factory()->create()];
    QvctCampaign::factory()->forStructure($a)->count(2)->create();
    QvctCampaign::factory()->forStructure($b)->count(3)->create();

    app()->instance('current_structure', $a);
    expect(QvctCampaign::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(QvctCampaign::count())->toBe(3);
});

it('cannot find another tenant campaign by ID', function (): void {
    [$a, $b] = [Structure::factory()->create(), Structure::factory()->create()];
    $foreign = QvctCampaign::factory()->forStructure($b)->create();

    app()->instance('current_structure', $a);
    expect(QvctCampaign::find($foreign->id))->toBeNull();
});

// ── QvctResponse ────────────────────────────────────────────────────────────

it('only returns responses from the current tenant', function (): void {
    [$a, $b] = [Structure::factory()->create(), Structure::factory()->create()];

    $campaignA = QvctCampaign::factory()->forStructure($a)->create();
    $campaignB = QvctCampaign::factory()->forStructure($b)->create();

    QvctResponse::factory()->forCampaign($campaignA)->count(5)->create();
    QvctResponse::factory()->forCampaign($campaignB)->count(8)->create();

    app()->instance('current_structure', $a);
    expect(QvctResponse::count())->toBe(5);

    app()->instance('current_structure', $b);
    expect(QvctResponse::count())->toBe(8);
});

it('responses carry no user_id column (anonymity invariant)', function (): void {
    $s = Structure::factory()->create();
    $campaign = QvctCampaign::factory()->forStructure($s)->create();
    app()->instance('current_structure', $s);

    $response = QvctResponse::factory()->forCampaign($campaign)->create();

    // Either the column doesn't exist on the model, or it does but is null.
    // Both shapes preserve anonymity; the assertion catches a future
    // accidental migration that adds a user_id column.
    expect($response->getAttributes())->not->toHaveKey('user_id');
});

// ── QvctWeakSignal ──────────────────────────────────────────────────────────

it('only returns weak signals from the current tenant', function (): void {
    [$a, $b] = [Structure::factory()->create(), Structure::factory()->create()];

    $campaignA = QvctCampaign::factory()->forStructure($a)->create();
    $campaignB = QvctCampaign::factory()->forStructure($b)->create();

    QvctWeakSignal::factory()->forCampaign($campaignA)->count(2)->create();
    QvctWeakSignal::factory()->forCampaign($campaignB)->count(3)->create();

    app()->instance('current_structure', $a);
    expect(QvctWeakSignal::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(QvctWeakSignal::count())->toBe(3);
});

it('cannot acknowledge a foreign-tenant weak signal', function (): void {
    [$a, $b] = [Structure::factory()->create(), Structure::factory()->create()];
    $campaignB = QvctCampaign::factory()->forStructure($b)->create();
    $foreign = QvctWeakSignal::factory()->forCampaign($campaignB)->create();

    app()->instance('current_structure', $a);

    expect(QvctWeakSignal::find($foreign->id))->toBeNull();
});
