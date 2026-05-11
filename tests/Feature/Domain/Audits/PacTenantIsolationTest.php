<?php

declare(strict_types=1);

use App\Models\Pac;
use App\Models\PacAction;
use App\Models\Structure;

/**
 * Cross-tenant leak tests for the Plan d'Amélioration Continue tables
 * (M6.5 + M6.6). PHASE2_PROGRESS.md M6.8 partial.
 */
beforeEach(function (): void {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

it('returns zero PACs when no tenant is bound', function (): void {
    $structure = Structure::factory()->create();
    Pac::factory()->forStructure($structure)->count(2)->create();

    expect(Pac::count())->toBe(0);
});

it('only returns PACs from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    Pac::factory()->forStructure($a)->count(1)->create();
    Pac::factory()->forStructure($b)->count(3)->create();

    app()->instance('current_structure', $a);
    expect(Pac::count())->toBe(1);

    app()->instance('current_structure', $b);
    expect(Pac::count())->toBe(3);
});

it('cannot find a foreign-tenant PAC by id', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $foreign = Pac::factory()->forStructure($b)->create();

    app()->instance('current_structure', $a);
    expect(Pac::find($foreign->id))->toBeNull();
});

it('only returns PAC actions from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $pacA = Pac::factory()->forStructure($a)->create();
    $pacB = Pac::factory()->forStructure($b)->create();

    PacAction::factory()->forPac($pacA)->count(2)->create();
    PacAction::factory()->forPac($pacB)->count(4)->create();

    app()->instance('current_structure', $a);
    expect(PacAction::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(PacAction::count())->toBe(4);
});
