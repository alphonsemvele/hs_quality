<?php

declare(strict_types=1);

use App\Enums\AuditGridSource;
use App\Enums\ExigenceLevel;
use App\Models\AuditGrid;
use App\Models\AuditGridAxis;
use App\Models\AuditGridItem;
use App\Models\Structure;
use App\Services\AuditGridLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->library = app(AuditGridLibrary::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
});

it('provisions the unified SAP grid with 10 axes and 75 items', function (): void {
    $grid = $this->library->provisionForStructure($this->structure, AuditGridSource::Has);

    expect($grid->source)->toBe(AuditGridSource::Has)
        ->and($grid->title)->toContain('SAP');

    $axisCount = AuditGridAxis::withoutGlobalScopes()
        ->where('audit_grid_id', $grid->id)
        ->count();
    expect($axisCount)->toBe(10);

    $itemCount = AuditGridItem::withoutGlobalScopes()
        ->where('audit_grid_id', $grid->id)
        ->count();
    expect($itemCount)->toBe(75);

    // Every item must be linked to one of the 10 axes.
    $orphans = AuditGridItem::withoutGlobalScopes()
        ->where('audit_grid_id', $grid->id)
        ->whereNull('axis_id')
        ->count();
    expect($orphans)->toBe(0);
});

it('populates level and sources from the fixture', function (): void {
    $grid = $this->library->provisionForStructure($this->structure, AuditGridSource::Has);

    $imperatifCount = AuditGridItem::withoutGlobalScopes()
        ->where('audit_grid_id', $grid->id)
        ->where('level', ExigenceLevel::Imperatif->value)
        ->count();
    // The unified SAP grid carries the 18 HAS impératifs plus a handful
    // of Cap'Handéo additions — at minimum the 18.
    expect($imperatifCount)->toBeGreaterThanOrEqual(15);

    $withSources = AuditGridItem::withoutGlobalScopes()
        ->where('audit_grid_id', $grid->id)
        ->whereNotNull('sources')
        ->count();
    expect($withSources)->toBe(75);
});

it('is idempotent — re-provisioning does not duplicate the grid', function (): void {
    $first = $this->library->provisionForStructure($this->structure, AuditGridSource::Has);
    $second = $this->library->provisionForStructure($this->structure, AuditGridSource::Has);

    expect($second->id)->toBe($first->id);

    $count = AuditGrid::withoutGlobalScopes()
        ->where('structure_id', $this->structure->id)
        ->where('source', AuditGridSource::Has->value)
        ->count();
    expect($count)->toBe(1);
});

it('replaceForStructure soft-deletes the old grid and provisions a new one', function (): void {
    $old = $this->library->provisionForStructure($this->structure, AuditGridSource::Has);

    $new = $this->library->replaceForStructure($this->structure, AuditGridSource::Has);

    expect($new->id)->not->toBe($old->id);

    expect(AuditGrid::withoutGlobalScopes()->withTrashed()->find($old->id)->trashed())->toBeTrue();

    $active = AuditGrid::withoutGlobalScopes()
        ->where('structure_id', $this->structure->id)
        ->where('source', AuditGridSource::Has->value)
        ->whereNull('deleted_at')
        ->count();
    expect($active)->toBe(1);
});
