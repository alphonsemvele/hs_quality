<?php

declare(strict_types=1);

use App\Jobs\GenerateSectorBenchmarkJob;
use App\Models\SectorBenchmarkSnapshot;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Queue::fake();
});

it('returns 404 when no benchmark snapshot exists', function (): void {
    actingAsApiRole('dirigeant');

    $this->getJson('/api/v1/benchmark/sector')->assertNotFound();
});

it('returns the latest snapshot for a dirigeant', function (): void {
    actingAsApiRole('dirigeant');

    SectorBenchmarkSnapshot::factory()->create([
        'snapshot_month' => now()->startOfMonth()->toDateString(),
        'interventions_data' => [],
        'incidents_data' => [],
        'qvct_data' => [],
        'conformity_data' => [],
    ]);

    $this->getJson('/api/v1/benchmark/sector')
        ->assertSuccessful()
        ->assertJsonStructure(['snapshot_month', 'interventions', 'incidents', 'qvct', 'conformity']);
});

it('coordinateur cannot view benchmark (lacks benchmark.sector.view)', function (): void {
    actingAsApiRole('coordinateur');

    $this->getJson('/api/v1/benchmark/sector')->assertForbidden();
});

it('platform admin can trigger on-demand snapshot generation', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);

    $admin = User::factory()->forStructure($structure)->create([
        'is_platform_admin' => true,
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());
    $admin->givePermissionTo('cross_tenant_benchmark.read');
    test()->actingAs($admin, 'sanctum');

    $this->postJson('/api/v1/benchmark/sector/generate')
        ->assertStatus(202);

    Queue::assertPushed(GenerateSectorBenchmarkJob::class);
});

it('non-admin cannot trigger snapshot generation', function (): void {
    actingAsApiRole('dirigeant');

    $this->postJson('/api/v1/benchmark/sector/generate')->assertForbidden();
});
