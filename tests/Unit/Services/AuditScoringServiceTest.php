<?php

declare(strict_types=1);

use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\Structure;
use App\Services\AuditScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(AuditScoringService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
});

it('returns zeros for a grid with no items', function (): void {
    $grid = AuditGrid::factory()->forStructure($this->structure)->create();
    $run = AuditRun::factory()->forGrid($grid)->create();

    $aggregates = $this->service->score($run);

    expect($aggregates['total_score'])->toBe(0.0)
        ->and($aggregates['max_score'])->toBe(0.0)
        ->and($aggregates['percentage'])->toBe(0.0);
});

it('computes total / max / percentage from responses', function (): void {
    $grid = AuditGrid::factory()->forStructure($this->structure)->create();
    $items = AuditGridItem::factory()->forGrid($grid)->count(4)
        ->state(['max_points' => 5])
        ->create();
    $run = AuditRun::factory()->forGrid($grid)->create();

    foreach ($items as $i => $item) {
        AuditRunResponse::factory()->forRunAndItem($run, $item)->create([
            'score' => $i === 3 ? 1 : 5, // 3 items perfect, 1 item poor
        ]);
    }

    $aggregates = $this->service->score($run);

    expect((float) $aggregates['max_score'])->toBe(20.0)
        ->and((float) $aggregates['total_score'])->toBe(16.0)
        ->and((float) $aggregates['percentage'])->toBe(80.0);
});

it('counts all grid items in max_score even if unanswered', function (): void {
    $grid = AuditGrid::factory()->forStructure($this->structure)->create();
    $items = AuditGridItem::factory()->forGrid($grid)->count(3)
        ->state(['max_points' => 10])
        ->create();
    $run = AuditRun::factory()->forGrid($grid)->create();

    // Only one of three items has a response — partial completion should
    // surface as a low percentage.
    AuditRunResponse::factory()->forRunAndItem($run, $items->first())->create([
        'score' => 10,
    ]);

    $aggregates = $this->service->score($run);

    expect((float) $aggregates['max_score'])->toBe(30.0)
        ->and((float) $aggregates['total_score'])->toBe(10.0)
        ->and((float) $aggregates['percentage'])->toBeBetween(33.0, 33.5);
});
