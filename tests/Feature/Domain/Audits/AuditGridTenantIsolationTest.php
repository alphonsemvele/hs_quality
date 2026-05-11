<?php

declare(strict_types=1);

use App\Models\AuditGrid;
use App\Models\Structure;

/**
 * Mandatory cross-tenant leak test for the AuditGrid domain model
 * (Phase 2 / M6 — first slice). Same shape as Phase 1 isolation tests
 * so a future audit can grep them all uniformly.
 */
beforeEach(function (): void {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

it('returns zero audit grids when no tenant is bound', function (): void {
    $structure = Structure::factory()->create();
    AuditGrid::factory()->forStructure($structure)->count(3)->create();

    expect(AuditGrid::count())->toBe(0);
});

it('only returns audit grids from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    AuditGrid::factory()->forStructure($a)->count(2)->create();
    AuditGrid::factory()->forStructure($b)->count(4)->create();

    app()->instance('current_structure', $a);
    expect(AuditGrid::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(AuditGrid::count())->toBe(4);
});

it('cannot find another tenant audit grid by ID', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $foreign = AuditGrid::factory()->forStructure($b)->create();

    app()->instance('current_structure', $a);
    expect(AuditGrid::find($foreign->id))->toBeNull();
});

it('auto-populates structure_id from current tenant on create', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);

    $grid = AuditGrid::create([
        'title' => 'Custom HAS',
        'source' => 'has',
    ]);

    expect($grid->structure_id)->toBe($structure->id);
});
