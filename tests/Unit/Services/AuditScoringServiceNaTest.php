<?php

declare(strict_types=1);

use App\Enums\AuditItemScale;
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

function makeHasCotationGrid(Structure $structure, int $items = 5): array
{
    $grid = AuditGrid::factory()->forStructure($structure)->create();
    $items = AuditGridItem::factory()->forGrid($grid)->count($items)->state([
        'scale' => AuditItemScale::HasCotation->value,
        'max_points' => 4,
    ])->create();
    $run = AuditRun::factory()->forGrid($grid)->create();

    return [$grid, $items, $run];
}

it('excludes NA items from numerator AND denominator', function (): void {
    [$grid, $items, $run] = makeHasCotationGrid($this->structure, 4);

    // 2 items A (score=4), 1 item C (score=2), 1 item NA (score=null).
    AuditRunResponse::factory()->forRunAndItem($run, $items[0])->create(['cotation' => 'A', 'score' => 4]);
    AuditRunResponse::factory()->forRunAndItem($run, $items[1])->create(['cotation' => 'A', 'score' => 4]);
    AuditRunResponse::factory()->forRunAndItem($run, $items[2])->create(['cotation' => 'C', 'score' => 2]);
    AuditRunResponse::factory()->forRunAndItem($run, $items[3])->create(['cotation' => 'NA', 'score' => null]);

    $a = $this->service->score($run);

    // Max = 3 items × 4 = 12 (NA item excluded). Total = 4+4+2 = 10.
    expect((float) $a['max_score'])->toBe(12.0)
        ->and((float) $a['total_score'])->toBe(10.0)
        ->and((float) $a['percentage'])->toBeBetween(83.0, 83.5)
        ->and($a['excluded_count'])->toBe(1)
        ->and($a['evaluated_count'])->toBe(3);
});

it('handles a run where every item is NA', function (): void {
    [$grid, $items, $run] = makeHasCotationGrid($this->structure, 3);

    foreach ($items as $it) {
        AuditRunResponse::factory()->forRunAndItem($run, $it)->create(['cotation' => 'NA', 'score' => null]);
    }

    $a = $this->service->score($run);

    expect((float) $a['max_score'])->toBe(0.0)
        ->and((float) $a['total_score'])->toBe(0.0)
        ->and((float) $a['percentage'])->toBe(0.0)
        ->and($a['excluded_count'])->toBe(3)
        ->and($a['evaluated_count'])->toBe(0);
});

it('computes conformity_rate and gap_rate excluding NA', function (): void {
    [$grid, $items, $run] = makeHasCotationGrid($this->structure, 5);

    // 1 A, 1 B, 1 C, 1 D, 1 NA.
    AuditRunResponse::factory()->forRunAndItem($run, $items[0])->create(['cotation' => 'A', 'score' => 4]);
    AuditRunResponse::factory()->forRunAndItem($run, $items[1])->create(['cotation' => 'B', 'score' => 3]);
    AuditRunResponse::factory()->forRunAndItem($run, $items[2])->create(['cotation' => 'C', 'score' => 2]);
    AuditRunResponse::factory()->forRunAndItem($run, $items[3])->create(['cotation' => 'D', 'score' => 1]);
    AuditRunResponse::factory()->forRunAndItem($run, $items[4])->create(['cotation' => 'NA', 'score' => null]);

    $a = $this->service->score($run);

    // 2 conformes (A+B) / 4 évalués = 50%, 2 écarts (C+D) / 4 = 50%.
    expect((float) $a['conformity_rate'])->toBe(50.0)
        ->and((float) $a['gap_rate'])->toBe(50.0)
        ->and($a['excluded_count'])->toBe(1)
        ->and($a['evaluated_count'])->toBe(4);
});
