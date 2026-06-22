<?php

declare(strict_types=1);

use App\Models\AuditGrid;
use App\Models\AuditGridAxis;
use App\Models\Structure;

/**
 * Mandatory cross-tenant leak test for AuditGridAxis (introduced with
 * the unified SAP HAS grid). Same shape as the existing AuditGrid /
 * AuditGridItem isolation tests so a future audit can grep them all
 * uniformly.
 */
beforeEach(function (): void {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

it('returns zero axes when no tenant is bound', function (): void {
    $structure = Structure::factory()->create();
    $grid = AuditGrid::factory()->forStructure($structure)->create();
    AuditGridAxis::factory()->forGrid($grid)->count(3)->create();

    expect(AuditGridAxis::count())->toBe(0);
});

it('only returns axes from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $gridA = AuditGrid::factory()->forStructure($a)->create();
    $gridB = AuditGrid::factory()->forStructure($b)->create();

    AuditGridAxis::factory()->forGrid($gridA)->count(2)->create();
    AuditGridAxis::factory()->forGrid($gridB)->count(5)->create();

    app()->instance('current_structure', $a);
    expect(AuditGridAxis::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(AuditGridAxis::count())->toBe(5);
});

it('cannot find another tenant axis by ID', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $gridB = AuditGrid::factory()->forStructure($b)->create();
    $foreign = AuditGridAxis::factory()->forGrid($gridB)->create();

    app()->instance('current_structure', $a);
    expect(AuditGridAxis::find($foreign->id))->toBeNull();
});

it('auto-populates structure_id from current tenant on create', function (): void {
    $structure = Structure::factory()->create();
    $grid = AuditGrid::factory()->forStructure($structure)->create();
    app()->instance('current_structure', $structure);

    $axis = AuditGridAxis::create([
        'audit_grid_id' => $grid->id,
        'code' => 'axe_1',
        'title' => 'Test axis',
        'position' => 1,
    ]);

    expect($axis->structure_id)->toBe($structure->id);
});
