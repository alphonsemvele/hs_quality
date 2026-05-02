<?php

declare(strict_types=1);

use App\Enums\AuditGridSource;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\Structure;
use Database\Seeders\AuditGridSeeder;

it('seeds the HAS reference grid per structure', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    (new AuditGridSeeder)->run();

    expect(AuditGrid::withoutGlobalScopes()->where('structure_id', $a->id)->where('source', 'has')->count())->toBe(1);
    expect(AuditGrid::withoutGlobalScopes()->where('structure_id', $b->id)->where('source', 'has')->count())->toBe(1);
});

it('seeds the items defined in the JSON fixture', function (): void {
    Structure::factory()->create();

    (new AuditGridSeeder)->run();

    $grid = AuditGrid::withoutGlobalScopes()->where('source', 'has')->first();
    expect($grid)->not->toBeNull();

    $itemCount = AuditGridItem::withoutGlobalScopes()->where('audit_grid_id', $grid->id)->count();
    expect($itemCount)->toBeGreaterThanOrEqual(10); // fixture currently ships 10 items
});

it('is idempotent — re-running does not duplicate the HAS grid', function (): void {
    Structure::factory()->create();

    (new AuditGridSeeder)->run();
    (new AuditGridSeeder)->run();

    $count = AuditGrid::withoutGlobalScopes()
        ->where('source', AuditGridSource::Has->value)
        ->count();

    expect($count)->toBe(1);
});
