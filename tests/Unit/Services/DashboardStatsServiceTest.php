<?php

use App\Enums\InterventionStatus;
use App\Enums\QvctCampaignStatus;
use App\Models\Beneficiary;
use App\Models\Incident;
use App\Models\Intervention;
use App\Models\QvctCampaign;
use App\Models\QvctResponse;
use App\Models\QvctWeakSignal;
use App\Models\Structure;
use App\Models\User;
use App\Services\DashboardStatsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->service = app(DashboardStatsService::class);

    $this->intervenant = User::factory()->create([
        'structure_id' => $this->structure->id,
        'type' => 'intervenant',
    ]);
    $this->beneficiary = Beneficiary::factory()->forStructure($this->structure)->create();

    Carbon::setTestNow('2026-05-10 10:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
    Cache::flush();
});

// ── Intervention stats ─────────────────────────────────────────────────────────

it('counts interventions planned for today', function () {
    Intervention::factory()
        ->forBeneficiary($this->beneficiary)->forIntervenant($this->intervenant)
        ->planned()->create(['planned_date' => '2026-05-10']);

    Intervention::factory()
        ->forBeneficiary($this->beneficiary)->forIntervenant($this->intervenant)
        ->planned()->create(['planned_date' => '2026-05-09']);

    $stats = $this->service->stats($this->structure->id);

    expect($stats['interventions_today'])->toBe(1);
});

it('counts interventions for the current month', function () {
    Intervention::factory()
        ->forBeneficiary($this->beneficiary)->forIntervenant($this->intervenant)
        ->planned()->create(['planned_date' => '2026-05-01']);

    Intervention::factory()
        ->forBeneficiary($this->beneficiary)->forIntervenant($this->intervenant)
        ->planned()->create(['planned_date' => '2026-04-30']); // previous month

    $stats = $this->service->stats($this->structure->id);

    expect($stats['interventions_ce_mois'])->toBe(1);
});

it('counts in-progress interventions regardless of date', function () {
    Intervention::factory()
        ->forBeneficiary($this->beneficiary)->forIntervenant($this->intervenant)
        ->inProgress()->create(['planned_date' => '2026-05-09']);

    $stats = $this->service->stats($this->structure->id);

    expect($stats['in_progress_count'])->toBe(1);
});

it('counts completed and cancelled and missed for today', function () {
    Intervention::factory()
        ->forBeneficiary($this->beneficiary)->forIntervenant($this->intervenant)
        ->completed()->create(['planned_date' => '2026-05-10']);

    Intervention::factory()
        ->forBeneficiary($this->beneficiary)->forIntervenant($this->intervenant)
        ->cancelled()->create(['planned_date' => '2026-05-10']);

    Intervention::factory()
        ->forBeneficiary($this->beneficiary)->forIntervenant($this->intervenant)
        ->create(['planned_date' => '2026-05-10', 'status' => InterventionStatus::Missed->value]);

    $stats = $this->service->stats($this->structure->id);

    expect($stats['completed_today'])->toBe(1)
        ->and($stats['cancelled_today'])->toBe(1)
        ->and($stats['missed_today'])->toBe(1);
});

// ── Incident stats ─────────────────────────────────────────────────────────────

it('counts declared incidents', function () {
    Incident::factory()->forStructure($this->structure)
        ->create(['declared_by' => $this->intervenant->id]);

    $stats = $this->service->stats($this->structure->id);

    expect($stats['incidents_declares'])->toBe(1);
});

it('counts open incidents as en_cours', function () {
    Incident::factory()->forStructure($this->structure)->enAnalyse()
        ->create(['declared_by' => $this->intervenant->id]);
    Incident::factory()->forStructure($this->structure)->planActions()
        ->create(['declared_by' => $this->intervenant->id]);

    $stats = $this->service->stats($this->structure->id);

    expect($stats['incidents_en_cours'])->toBe(2);
});

it('counts grave and critique incidents that are not closed', function () {
    Incident::factory()->forStructure($this->structure)->grave()
        ->create(['declared_by' => $this->intervenant->id]);
    Incident::factory()->forStructure($this->structure)->critique()
        ->create(['declared_by' => $this->intervenant->id]);
    // Closed grave should NOT count
    Incident::factory()->forStructure($this->structure)->grave()->clos()
        ->create(['declared_by' => $this->intervenant->id]);

    $stats = $this->service->stats($this->structure->id);

    expect($stats['incidents_graves'])->toBe(2);
});

// ── Caching ────────────────────────────────────────────────────────────────────

it('serves stats from cache on second call without extra DB queries', function () {
    $sid = $this->structure->id;

    $this->service->stats($sid); // warm the cache

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $this->service->stats($sid); // should hit cache, no DB queries

    expect($queryCount)->toBe(0);
});

it('invalidates cache when an intervention is written', function () {
    $sid = $this->structure->id;

    // Prime the cache with 0 interventions today
    $before = $this->service->stats($sid)['interventions_today'];
    expect($before)->toBe(0);

    // Create an intervention — observer flushes the cache tag
    Intervention::factory()
        ->forBeneficiary($this->beneficiary)->forIntervenant($this->intervenant)
        ->planned()->create(['planned_date' => '2026-05-10']);

    // Next call re-queries and returns updated count
    $after = $this->service->stats($sid)['interventions_today'];
    expect($after)->toBe(1);
});

it('invalidates cache when an incident is written', function () {
    $sid = $this->structure->id;

    $before = $this->service->stats($sid)['incidents_declares'];
    expect($before)->toBe(0);

    Incident::factory()->forStructure($this->structure)
        ->create(['declared_by' => $this->intervenant->id]);

    $after = $this->service->stats($sid)['incidents_declares'];
    expect($after)->toBe(1);
});

// ── QVCT stats (Phase 2 / M3 — PHASE2_PROGRESS.md M3.16) ──────────────────────

it('counts open QVCT campaigns', function () {
    QvctCampaign::factory()->forStructure($this->structure)->create([
        'status' => QvctCampaignStatus::Active->value,
    ]);
    QvctCampaign::factory()->forStructure($this->structure)->closed()->create();

    $stats = $this->service->stats($this->structure->id);

    expect($stats['qvct_campaigns_open'])->toBe(1);
});

it('counts QVCT responses submitted in the current month', function () {
    $campaign = QvctCampaign::factory()->forStructure($this->structure)->create();

    QvctResponse::factory()->forCampaign($campaign)->count(3)->create([
        'submitted_at' => Carbon::now(),
    ]);
    QvctResponse::factory()->forCampaign($campaign)->create([
        'submitted_at' => Carbon::now()->subMonth(),
    ]);

    $stats = $this->service->stats($this->structure->id);

    expect($stats['qvct_responses_ce_mois'])->toBe(3);
});

it('counts outstanding (un-acknowledged) QVCT weak signals', function () {
    $campaign = QvctCampaign::factory()->forStructure($this->structure)->create();

    QvctWeakSignal::factory()->forCampaign($campaign)->count(2)->create();
    QvctWeakSignal::factory()->forCampaign($campaign)->create([
        'acknowledged_by' => $this->intervenant->id,
        'acknowledged_at' => now(),
    ]);

    $stats = $this->service->stats($this->structure->id);

    expect($stats['qvct_weak_signals_outstanding'])->toBe(2);
});

it('invalidates cache when a QVCT campaign is launched', function () {
    $sid = $this->structure->id;

    $before = $this->service->stats($sid)['qvct_campaigns_open'];
    expect($before)->toBe(0);

    QvctCampaign::factory()->forStructure($this->structure)->create([
        'status' => QvctCampaignStatus::Active->value,
    ]);

    $after = $this->service->stats($sid)['qvct_campaigns_open'];
    expect($after)->toBe(1);
});

it('invalidates cache when a QVCT response is submitted', function () {
    $sid = $this->structure->id;
    $campaign = QvctCampaign::factory()->forStructure($this->structure)->create();

    $this->service->stats($sid); // warm

    QvctResponse::factory()->forCampaign($campaign)->create([
        'submitted_at' => Carbon::now(),
    ]);

    expect($this->service->stats($sid)['qvct_responses_ce_mois'])->toBe(1);
});
