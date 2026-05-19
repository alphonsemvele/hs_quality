<?php

declare(strict_types=1);

use App\Models\AuditGrid;
use App\Models\AuditRun;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\Structure;
use App\Models\User;
use App\Services\CrossTenantQueryService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    // Create a platform admin with the benchmark permission.
    $s1 = Structure::factory()->create(['type' => 'saad', 'tier' => 'pro']);
    app()->instance('current_structure', $s1);
    $this->admin = User::factory()->forStructure($s1)->create([
        'is_platform_admin' => true,
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($s1->getKey());
    $this->admin->givePermissionTo('cross_tenant_benchmark.read');
    $this->service = new CrossTenantQueryService($this->admin);

    $this->s1 = $s1;
    $this->s2 = Structure::factory()->create(['type' => 'ssiad', 'tier' => 'pro']);
});

it('throws 403 when caller lacks cross_tenant_benchmark.read', function (): void {
    $unprivileged = User::factory()->forStructure($this->s1)->create();

    expect(fn () => new CrossTenantQueryService($unprivileged))
        ->toThrow(HttpException::class);
});

it('sectorInterventionsBenchmark returns aggregated rows without structure_id', function (): void {
    $b1 = Beneficiary::factory()->forStructure($this->s1)->create();
    $b2 = Beneficiary::factory()->forStructure($this->s2)->create();

    Intervention::factory()->create([
        'structure_id' => $this->s1->id,
        'beneficiary_id' => $b1->id,
        'planned_date' => now()->subWeek(),
    ]);
    Intervention::factory()->create([
        'structure_id' => $this->s2->id,
        'beneficiary_id' => $b2->id,
        'planned_date' => now()->subWeek(),
    ]);

    $rows = $this->service->sectorInterventionsBenchmark(monthsBack: 3);

    expect($rows)->not->toBeEmpty();
    foreach ($rows as $row) {
        expect($row)->not->toHaveKey('structure_id');
        expect($row)->toHaveKey('type_structure');
        expect($row)->toHaveKey('nb_structures');
    }
});

it('sectorConformityBenchmark returns avg_conformite_pct per type+tier', function (): void {
    $grid = AuditGrid::factory()->forStructure($this->s1)->create();
    AuditRun::factory()->forGrid($grid)->finalised()->create([
        'score' => 8.0,
        'max_score' => 10.0,
    ]);

    $rows = $this->service->sectorConformityBenchmark();

    expect($rows)->not->toBeEmpty();
    expect((array) $rows[0])->toHaveKey('avg_conformite_pct');
});
