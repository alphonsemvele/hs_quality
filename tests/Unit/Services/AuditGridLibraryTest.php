<?php

declare(strict_types=1);

use App\Enums\AuditGridSource;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\Structure;
use App\Services\AuditGridLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Point the library at a tiny temp fixture dir for isolation.
function makeLibraryWithTempFixtures(): array
{
    $dir = sys_get_temp_dir().'/audit_grid_library_test_'.uniqid();
    mkdir($dir, 0755, true);

    file_put_contents($dir.'/has-grid.json', json_encode([
        'title' => 'Grille HAS test',
        'description' => 'Test',
        'weight_scheme' => ['type' => 'equal'],
        'items' => [
            ['title' => 'Item A', 'scale' => 'binary', 'max_points' => 1, 'evidence_required' => false, 'position' => 1],
            ['title' => 'Item B', 'scale' => '1_to_5', 'max_points' => 5, 'evidence_required' => true, 'position' => 2],
        ],
    ]));

    return [$dir, new AuditGridLibrary($dir)];
}

it('availableSources() returns only sources with fixture files', function (): void {
    [$dir, $library] = makeLibraryWithTempFixtures();

    $sources = $library->availableSources();

    expect($sources)->toHaveCount(1);
    expect($sources[0])->toBe(AuditGridSource::Has);
    expect($sources)->not->toContain(AuditGridSource::Custom);

    // Cleanup.
    unlink($dir.'/has-grid.json');
    rmdir($dir);
});

it('fixtureExists() returns false for Custom (no fixture file defined)', function (): void {
    [, $library] = makeLibraryWithTempFixtures();

    expect($library->fixtureExists(AuditGridSource::Custom))->toBeFalse();
});

it('loadFixture() decodes the JSON and returns an array', function (): void {
    [$dir, $library] = makeLibraryWithTempFixtures();

    $payload = $library->loadFixture(AuditGridSource::Has);

    expect($payload['title'])->toBe('Grille HAS test');
    expect($payload['items'])->toHaveCount(2);

    unlink($dir.'/has-grid.json');
    rmdir($dir);
});

it('loadFixture() throws RuntimeException when fixture is absent', function (): void {
    [$dir, $library] = makeLibraryWithTempFixtures();

    expect(fn () => $library->loadFixture(AuditGridSource::Iso9001))
        ->toThrow(RuntimeException::class);

    unlink($dir.'/has-grid.json');
    rmdir($dir);
});

it('provisionForStructure() creates an AuditGrid with its items', function (): void {
    [$dir, $library] = makeLibraryWithTempFixtures();
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);

    $grid = $library->provisionForStructure($structure, AuditGridSource::Has);

    expect($grid)->toBeInstanceOf(AuditGrid::class);
    expect($grid->structure_id)->toBe($structure->id);
    expect($grid->source->value)->toBe('has');

    $itemCount = AuditGridItem::withoutGlobalScopes()
        ->where('audit_grid_id', $grid->id)
        ->count();
    expect($itemCount)->toBe(2);

    unlink($dir.'/has-grid.json');
    rmdir($dir);
});

it('provisionForStructure() is idempotent — second call returns existing grid', function (): void {
    [$dir, $library] = makeLibraryWithTempFixtures();
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);

    $first = $library->provisionForStructure($structure, AuditGridSource::Has);
    $second = $library->provisionForStructure($structure, AuditGridSource::Has);

    expect($second->id)->toBe($first->id);
    expect(AuditGrid::withoutGlobalScopes()
        ->where('structure_id', $structure->id)
        ->where('source', 'has')
        ->count())->toBe(1);

    unlink($dir.'/has-grid.json');
    rmdir($dir);
});

it('provisionAllForStructure() provisions every available source', function (): void {
    $dir = sys_get_temp_dir().'/audit_grid_library_test_all_'.uniqid();
    mkdir($dir, 0755, true);

    $makeFixture = fn (string $title) => json_encode([
        'title' => $title,
        'weight_scheme' => ['type' => 'equal'],
        'items' => [['title' => 'Critère 1', 'scale' => 'binary', 'max_points' => 1, 'evidence_required' => false, 'position' => 1]],
    ]);

    file_put_contents($dir.'/has-grid.json', $makeFixture('HAS'));
    file_put_contents($dir.'/iso9001-grid.json', $makeFixture('ISO'));

    $library = new AuditGridLibrary($dir);
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);

    $library->provisionAllForStructure($structure);

    $count = AuditGrid::withoutGlobalScopes()
        ->where('structure_id', $structure->id)
        ->count();
    expect($count)->toBe(2);

    unlink($dir.'/has-grid.json');
    unlink($dir.'/iso9001-grid.json');
    rmdir($dir);
});
