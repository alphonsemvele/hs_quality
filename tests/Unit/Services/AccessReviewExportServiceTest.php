<?php

declare(strict_types=1);

use App\Models\Structure;
use App\Models\User;
use App\Services\AccessReviewExportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->service = app(AccessReviewExportService::class);
});

it('yields one row per (structure, user) and resolves Spatie roles + permissions per tenant team', function (): void {
    $structureA = Structure::factory()->create(['code' => 'AAA-001']);
    $structureB = Structure::factory()->create(['code' => 'BBB-001']);

    $alice = User::factory()->forStructure($structureA)->create([
        'email' => 'alice@example.fr',
        'type' => 'coordinateur',
    ]);
    $bob = User::factory()->forStructure($structureB)->create([
        'email' => 'bob@example.fr',
        'type' => 'intervenant',
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($structureA->id);
    $alice->assignRole('coordinateur');

    app(PermissionRegistrar::class)->setPermissionsTeamId($structureB->id);
    $bob->assignRole('intervenant');

    $rows = collect(iterator_to_array($this->service->rows(), false));

    expect($rows)->toHaveCount(2);

    $rowA = $rows->firstWhere('email', 'alice@example.fr');
    expect($rowA['structure_code'])->toBe('AAA-001');
    expect($rowA['roles'])->toBe('coordinateur');
    expect($rowA['permissions_count'])->toBeGreaterThan(0);

    $rowB = $rows->firstWhere('email', 'bob@example.fr');
    expect($rowB['structure_code'])->toBe('BBB-001');
    expect($rowB['roles'])->toBe('intervenant');
});

it('filters to a single structure when an id is passed', function (): void {
    $structureA = Structure::factory()->create();
    $structureB = Structure::factory()->create();

    User::factory()->forStructure($structureA)->create();
    User::factory()->forStructure($structureA)->create();
    User::factory()->forStructure($structureB)->create();

    $rows = collect(iterator_to_array($this->service->rows($structureA->id), false));

    expect($rows)->toHaveCount(2);
    expect($rows->pluck('structure_id')->unique()->all())->toBe([$structureA->id]);
});

it('marks mfa_enrolled = oui when two_factor_confirmed_at is set', function (): void {
    $structure = Structure::factory()->create();
    User::factory()->forStructure($structure)->create([
        'two_factor_confirmed_at' => now(),
    ]);

    $rows = collect(iterator_to_array($this->service->rows($structure->id), false));

    expect($rows->first()['mfa_enrolled'])->toBe('oui');
});

it('emits no rows for a structure without users', function (): void {
    $structure = Structure::factory()->create();

    $rows = iterator_to_array($this->service->rows($structure->id), false);

    expect($rows)->toBe([]);
});
