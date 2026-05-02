<?php

declare(strict_types=1);

use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\Structure;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Cross-tenant leak tests for the M6 execution chain
 * (grid_items + runs + responses). PHASE2_PROGRESS.md M6.8 partial.
 * Same shape as Phase 1 isolation tests so a future audit can grep
 * them all uniformly.
 */
beforeEach(function (): void {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

// ── AuditGridItem ──────────────────────────────────────────────────────────

it('only returns audit grid items from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $gridA = AuditGrid::factory()->forStructure($a)->create();
    $gridB = AuditGrid::factory()->forStructure($b)->create();

    AuditGridItem::factory()->forGrid($gridA)->count(2)->create();
    AuditGridItem::factory()->forGrid($gridB)->count(3)->create();

    app()->instance('current_structure', $a);
    expect(AuditGridItem::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(AuditGridItem::count())->toBe(3);
});

// ── AuditRun ──────────────────────────────────────────────────────────────

it('only returns audit runs from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $gridA = AuditGrid::factory()->forStructure($a)->create();
    $gridB = AuditGrid::factory()->forStructure($b)->create();

    AuditRun::factory()->forGrid($gridA)->count(1)->create();
    AuditRun::factory()->forGrid($gridB)->count(4)->create();

    app()->instance('current_structure', $a);
    expect(AuditRun::count())->toBe(1);

    app()->instance('current_structure', $b);
    expect(AuditRun::count())->toBe(4);
});

it('cannot find a foreign-tenant audit run by id', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $gridB = AuditGrid::factory()->forStructure($b)->create();
    $foreignRun = AuditRun::factory()->forGrid($gridB)->create();

    app()->instance('current_structure', $a);
    expect(AuditRun::find($foreignRun->id))->toBeNull();
});

// ── AuditRunResponse ──────────────────────────────────────────────────────

it('only returns audit run responses from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $gridA = AuditGrid::factory()->forStructure($a)->create();
    $itemA = AuditGridItem::factory()->forGrid($gridA)->create();
    $runA = AuditRun::factory()->forGrid($gridA)->create();
    AuditRunResponse::factory()->forRunAndItem($runA, $itemA)->create();

    $gridB = AuditGrid::factory()->forStructure($b)->create();
    $itemB = AuditGridItem::factory()->forGrid($gridB)->create();
    $runB = AuditRun::factory()->forGrid($gridB)->create();
    AuditRunResponse::factory()->forRunAndItem($runB, $itemB)->create();

    app()->instance('current_structure', $a);
    expect(AuditRunResponse::count())->toBe(1);

    app()->instance('current_structure', $b);
    expect(AuditRunResponse::count())->toBe(1);
});

it('enforces unique (audit_run_id, audit_grid_item_id) for responses', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);

    $grid = AuditGrid::factory()->forStructure($structure)->create();
    $item = AuditGridItem::factory()->forGrid($grid)->create();
    $run = AuditRun::factory()->forGrid($grid)->create();

    AuditRunResponse::factory()->forRunAndItem($run, $item)->create();

    expect(fn () => AuditRunResponse::factory()->forRunAndItem($run, $item)->create())
        ->toThrow(UniqueConstraintViolationException::class);
});
