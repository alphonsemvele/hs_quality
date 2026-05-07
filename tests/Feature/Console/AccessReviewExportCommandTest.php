<?php

declare(strict_types=1);

use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->tmpFile = tempnam(sys_get_temp_dir(), 'access-review-').'.csv';
});

afterEach(function (): void {
    if (isset($this->tmpFile) && is_file($this->tmpFile)) {
        unlink($this->tmpFile);
    }
});

it('writes a CSV with header + one row per user across all structures', function (): void {
    $structureA = Structure::factory()->create(['code' => 'AAA-001']);
    $structureB = Structure::factory()->create(['code' => 'BBB-001']);

    $alice = User::factory()->forStructure($structureA)->create(['email' => 'alice@example.fr']);
    $bob = User::factory()->forStructure($structureB)->create(['email' => 'bob@example.fr']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($structureA->id);
    $alice->assignRole('coordinateur');
    app(PermissionRegistrar::class)->setPermissionsTeamId($structureB->id);
    $bob->assignRole('intervenant');

    $exitCode = $this->artisan('access-review:export', ['--output' => $this->tmpFile])
        ->assertSuccessful()
        ->run();

    expect($exitCode)->toBe(0);

    $contents = file_get_contents($this->tmpFile);
    expect($contents)->toContain('email,type,statut,mfa_enrolled');
    expect($contents)->toContain('alice@example.fr');
    expect($contents)->toContain('bob@example.fr');
    expect($contents)->toContain('AAA-001');
    expect($contents)->toContain('BBB-001');

    // Header line + 2 data lines = 3 lines (trailing newline tolerated).
    $lineCount = count(array_filter(explode("\n", trim($contents))));
    expect($lineCount)->toBe(3);
});

it('honours --structure to scope the export to a single tenant', function (): void {
    $structureA = Structure::factory()->create(['code' => 'AAA-001']);
    $structureB = Structure::factory()->create(['code' => 'BBB-001']);

    User::factory()->forStructure($structureA)->create(['email' => 'alice@example.fr']);
    User::factory()->forStructure($structureB)->create(['email' => 'bob@example.fr']);

    $this->artisan('access-review:export', [
        '--structure' => $structureA->id,
        '--output' => $this->tmpFile,
    ])->assertSuccessful();

    $contents = file_get_contents($this->tmpFile);
    expect($contents)->toContain('alice@example.fr');
    expect($contents)->not->toContain('bob@example.fr');
});

it('writes only headers (zero data rows) when there are no users', function (): void {
    $this->artisan('access-review:export', ['--output' => $this->tmpFile])
        ->assertSuccessful();

    $contents = file_get_contents($this->tmpFile);
    expect($contents)->toBe('');
});
