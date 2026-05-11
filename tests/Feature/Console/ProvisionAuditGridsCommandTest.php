<?php

declare(strict_types=1);

use App\Enums\AuditGridSource;
use App\Models\AuditGrid;
use App\Models\Structure;
use App\Services\AuditGridLibrary;

it('provisions the HAS grid for every structure when called without options', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $this->artisan('audit-grids:provision')->assertSuccessful();

    expect(
        AuditGrid::withoutGlobalScopes()
            ->where('structure_id', $a->id)
            ->where('source', AuditGridSource::Has->value)
            ->count()
    )->toBe(1);

    expect(
        AuditGrid::withoutGlobalScopes()
            ->where('structure_id', $b->id)
            ->where('source', AuditGridSource::Has->value)
            ->count()
    )->toBe(1);
});

it('provisions all available sources for every structure', function (): void {
    $structure = Structure::factory()->create();

    $this->artisan('audit-grids:provision')->assertSuccessful();

    $library = app(AuditGridLibrary::class);
    $expectedCount = count($library->availableSources());

    expect(
        AuditGrid::withoutGlobalScopes()
            ->where('structure_id', $structure->id)
            ->count()
    )->toBe($expectedCount);
});

it('scopes provisioning to a single structure via --structure', function (): void {
    $target = Structure::factory()->create();
    $other = Structure::factory()->create();

    $this->artisan('audit-grids:provision', ['--structure' => $target->id])
        ->assertSuccessful();

    expect(
        AuditGrid::withoutGlobalScopes()
            ->where('structure_id', $target->id)
            ->count()
    )->toBeGreaterThanOrEqual(1);

    // Other structure untouched.
    expect(
        AuditGrid::withoutGlobalScopes()
            ->where('structure_id', $other->id)
            ->count()
    )->toBe(0);
});

it('scopes provisioning to a single source via --source', function (): void {
    $structure = Structure::factory()->create();

    $this->artisan('audit-grids:provision', ['--source' => 'has'])
        ->assertSuccessful();

    expect(
        AuditGrid::withoutGlobalScopes()
            ->where('structure_id', $structure->id)
            ->where('source', 'has')
            ->count()
    )->toBe(1);

    // ISO 9001 and AFNOR not provisioned.
    expect(
        AuditGrid::withoutGlobalScopes()
            ->where('structure_id', $structure->id)
            ->where('source', 'iso_9001')
            ->count()
    )->toBe(0);
});

it('is idempotent — running twice does not duplicate grids', function (): void {
    $structure = Structure::factory()->create();

    $this->artisan('audit-grids:provision')->assertSuccessful();
    $this->artisan('audit-grids:provision')->assertSuccessful();

    $library = app(AuditGridLibrary::class);
    $expectedCount = count($library->availableSources());

    expect(
        AuditGrid::withoutGlobalScopes()
            ->where('structure_id', $structure->id)
            ->count()
    )->toBe($expectedCount);
});

it('prints a warning and exits 0 when no structures exist', function (): void {
    $this->artisan('audit-grids:provision')
        ->expectsOutputToContain('Aucune structure trouvée')
        ->assertSuccessful();
});
