<?php

declare(strict_types=1);

use App\Enums\AuditGridSource;
use App\Enums\AuditRunStatus;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\Pac;
use App\Models\PacAction;
use App\Models\Structure;
use App\Models\User;
use App\Services\HASPreparationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->grid = AuditGrid::factory()->forStructure($this->structure)->create([
        'source' => AuditGridSource::Has->value,
    ]);

    $this->itemA = AuditGridItem::factory()->create([
        'structure_id' => $this->structure->id,
        'audit_grid_id' => $this->grid->id,
        'title' => 'Critère A',
        'max_points' => 10,
        'position' => 1,
    ]);

    $this->itemB = AuditGridItem::factory()->create([
        'structure_id' => $this->structure->id,
        'audit_grid_id' => $this->grid->id,
        'title' => 'Critère B',
        'max_points' => 5,
        'position' => 2,
    ]);

    $finalisedBy = User::factory()->forStructure($this->structure)->create();

    $this->run = AuditRun::factory()->create([
        'structure_id' => $this->structure->id,
        'audit_grid_id' => $this->grid->id,
        'status' => AuditRunStatus::Finalised,
        'score' => 9.0,
        'max_score' => 15.0,
        'finalised_by' => $finalisedBy->id,
        'finalised_at' => now(),
    ]);

    $this->service = app(HASPreparationService::class);
});

it('returns overall_readiness_pct computed from run score / max_score', function (): void {
    // score=9, max_score=15 → 60%
    $report = $this->service->analyse($this->run);

    expect($report['overall_readiness_pct'])->toBe(60.0);
    expect($report['conformity_threshold_pct'])->toBe(70.0);
    expect($report['run_id'])->toBe($this->run->id);
    expect($report['grid_source'])->toBe('has');
});

it('classifies items correctly across the three conformity bands', function (): void {
    // itemA: 8/10 = 80% → conforme
    AuditRunResponse::factory()->create([
        'structure_id' => $this->structure->id,
        'audit_run_id' => $this->run->id,
        'audit_grid_item_id' => $this->itemA->id,
        'score' => 8.0,
    ]);

    // itemB: 1/5 = 20% → non_conforme
    AuditRunResponse::factory()->create([
        'structure_id' => $this->structure->id,
        'audit_run_id' => $this->run->id,
        'audit_grid_item_id' => $this->itemB->id,
        'score' => 1.0,
    ]);

    $report = $this->service->analyse($this->run);

    $byItemId = collect($report['items'])->keyBy('item_id');

    expect($byItemId[$this->itemA->id]['status'])->toBe('conforme');
    expect($byItemId[$this->itemB->id]['status'])->toBe('non_conforme');
});

it('treats an item with no response as non_conforme with score 0', function (): void {
    // No response recorded for itemA or itemB.
    $report = $this->service->analyse($this->run);

    foreach ($report['items'] as $item) {
        expect($item['status'])->toBe('non_conforme');
        expect($item['score'])->toBe(0.0);
        expect($item['response_id'])->toBeNull();
    }
});

it('orders items: non_conforme before a_ameliorer before conforme, then by gap descending', function (): void {
    // itemA: 8/10 = 80% conforme, gap = 2
    // itemB: 2/5  = 40% a_ameliorer, gap = 3
    AuditRunResponse::factory()->create([
        'structure_id' => $this->structure->id,
        'audit_run_id' => $this->run->id,
        'audit_grid_item_id' => $this->itemA->id,
        'score' => 8.0,
    ]);
    AuditRunResponse::factory()->create([
        'structure_id' => $this->structure->id,
        'audit_run_id' => $this->run->id,
        'audit_grid_item_id' => $this->itemB->id,
        'score' => 2.0,
    ]);

    $report = $this->service->analyse($this->run);
    $statuses = array_column($report['items'], 'status');

    // a_ameliorer (priority 2) should come before conforme (priority 3).
    expect($statuses[0])->toBe('a_ameliorer');
    expect($statuses[1])->toBe('conforme');
});

it('surfaces pac_action_ids for items that have linked PAC actions', function (): void {
    $response = AuditRunResponse::factory()->create([
        'structure_id' => $this->structure->id,
        'audit_run_id' => $this->run->id,
        'audit_grid_item_id' => $this->itemB->id,
        'score' => 1.0,
    ]);

    $pac = Pac::factory()->create(['structure_id' => $this->structure->id]);
    $action = PacAction::factory()->create([
        'structure_id' => $this->structure->id,
        'pac_id' => $pac->id,
        'source_audit_response_id' => $response->id,
    ]);

    $report = $this->service->analyse($this->run);
    $itemReport = collect($report['items'])->firstWhere('item_id', $this->itemB->id);

    expect($itemReport['pac_action_ids'])->toContain($action->id);
});

it('returns correct summary counts', function (): void {
    // itemA → 80% conforme; itemB → no response → non_conforme
    AuditRunResponse::factory()->create([
        'structure_id' => $this->structure->id,
        'audit_run_id' => $this->run->id,
        'audit_grid_item_id' => $this->itemA->id,
        'score' => 8.0,
    ]);

    $report = $this->service->analyse($this->run);

    expect($report['summary']['conforme'])->toBe(1);
    expect($report['summary']['non_conforme'])->toBe(1);
    expect($report['summary']['a_ameliorer'])->toBe(0);
    expect($report['summary']['total'])->toBe(2);
});

it('throws 409 for a draft run', function (): void {
    $this->run->update(['status' => AuditRunStatus::Draft]);

    expect(fn () => $this->service->analyse($this->run->fresh()))
        ->toThrow(HttpException::class);
});

it('throws 422 for a non-HAS grid', function (): void {
    $isoGrid = AuditGrid::factory()->forStructure($this->structure)->create([
        'source' => AuditGridSource::Iso9001->value,
    ]);

    $isoRun = AuditRun::factory()->create([
        'structure_id' => $this->structure->id,
        'audit_grid_id' => $isoGrid->id,
        'status' => AuditRunStatus::Finalised,
        'score' => 5.0,
        'max_score' => 10.0,
        'finalised_by' => User::factory()->forStructure($this->structure)->create()->id,
        'finalised_at' => now(),
    ]);

    expect(fn () => $this->service->analyse($isoRun))
        ->toThrow(HttpException::class);
});
