<?php

declare(strict_types=1);

use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('provisions a tenant via flags non-interactively', function (): void {
    $exitCode = $this->artisan('tenant:provision', [
        '--code' => 'CLI-SAAD-1',
        '--name' => 'CLI Test SAAD',
        '--type' => 'saad',
        '--tier' => 'pro',
        '--siret' => '12345678901234',
        '--dirigeant-first-name' => 'Marie',
        '--dirigeant-last-name' => 'Durand',
        '--dirigeant-email' => 'cli-dir@test.fr',
    ])->expectsOutputToContain('Provisioned structure')
        ->run();

    expect($exitCode)->toBe(0);

    $structure = Structure::where('code', 'CLI-SAAD-1')->firstOrFail();
    expect($structure->name)->toBe('CLI Test SAAD');
    expect($structure->siret)->toBe('12345678901234');

    $dirigeant = User::where('email', 'cli-dir@test.fr')->firstOrFail();
    expect($dirigeant->structure_id)->toBe($structure->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->id);
    expect($dirigeant->fresh()->hasRole('dirigeant'))->toBeTrue();
});

it('rejects an invalid SIRET (must be 14 digits)', function (): void {
    $this->artisan('tenant:provision', [
        '--code' => 'CLI-SAAD-2',
        '--name' => 'X',
        '--type' => 'saad',
        '--siret' => 'not-numeric',
        '--dirigeant-first-name' => 'A',
        '--dirigeant-last-name' => 'B',
        '--dirigeant-email' => 'x@y.fr',
    ])->assertFailed();

    expect(Structure::where('code', 'CLI-SAAD-2')->exists())->toBeFalse();
});

it('rejects a duplicate structure code', function (): void {
    Structure::factory()->create(['code' => 'DUP-CODE']);

    $this->artisan('tenant:provision', [
        '--code' => 'DUP-CODE',
        '--name' => 'X',
        '--type' => 'saad',
        '--dirigeant-first-name' => 'A',
        '--dirigeant-last-name' => 'B',
        '--dirigeant-email' => 'unique@y.fr',
    ])->assertFailed();
});
