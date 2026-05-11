<?php

declare(strict_types=1);

use App\Models\QvctIndicator;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
});

function makeIndicatorUser(string $role, Structure $structure): User
{
    $user = User::factory()->forStructure($structure)->state(['type' => $role])->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());
    $user->assignRole($role);

    return $user;
}

// ── Tenant scope ──────────────────────────────────────────────────────────────

it('only returns indicators from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    // (structure_id, period_start) is unique — assign distinct months
    // explicitly so count() multiplications never collide.
    QvctIndicator::factory()->forStructure($a)->count(2)
        ->state(new Sequence(
            ['period_start' => '2026-01-01', 'period_end' => '2026-01-31'],
            ['period_start' => '2026-02-01', 'period_end' => '2026-02-28'],
        ))
        ->create();

    QvctIndicator::factory()->forStructure($b)->count(3)
        ->state(new Sequence(
            ['period_start' => '2026-01-01', 'period_end' => '2026-01-31'],
            ['period_start' => '2026-02-01', 'period_end' => '2026-02-28'],
            ['period_start' => '2026-03-01', 'period_end' => '2026-03-31'],
        ))
        ->create();

    app()->instance('current_structure', $a);
    expect(QvctIndicator::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(QvctIndicator::count())->toBe(3);
});

// ── Policy / RBAC ─────────────────────────────────────────────────────────────

it('rh can view + edit indicators', function (): void {
    $rh = makeIndicatorUser('rh', $this->structure);
    $indicator = QvctIndicator::factory()->forStructure($this->structure)->create();

    expect($rh->can('viewAny', QvctIndicator::class))->toBeTrue()
        ->and($rh->can('view', $indicator))->toBeTrue()
        ->and($rh->can('update', $indicator))->toBeTrue()
        ->and($rh->can('create', QvctIndicator::class))->toBeTrue();
});

it('intervenant cannot view or edit indicators', function (): void {
    $intervenant = makeIndicatorUser('intervenant', $this->structure);
    $indicator = QvctIndicator::factory()->forStructure($this->structure)->create();

    expect($intervenant->can('viewAny', QvctIndicator::class))->toBeFalse()
        ->and($intervenant->can('view', $indicator))->toBeFalse()
        ->and($intervenant->can('update', $indicator))->toBeFalse();
});

it('coordinateur cannot edit indicators (only view via dirigeant aggregate path)', function (): void {
    $coord = makeIndicatorUser('coordinateur', $this->structure);
    $indicator = QvctIndicator::factory()->forStructure($this->structure)->create();

    expect($coord->can('update', $indicator))->toBeFalse();
});

it('cannot view a foreign-tenant indicator', function (): void {
    $foreignStructure = Structure::factory()->create();
    $foreignRh = makeIndicatorUser('rh', $foreignStructure);

    app()->instance('current_structure', $this->structure);
    $local = QvctIndicator::factory()->forStructure($this->structure)->create();

    expect($foreignRh->can('view', $local))->toBeFalse();
});

// ── HTTP API ──────────────────────────────────────────────────────────────────

it('rh can list indicators via API', function (): void {
    $rh = actingAsApiRole('rh');
    QvctIndicator::factory()->forStructure($rh->structure)->count(3)
        ->state(new Sequence(
            ['period_start' => '2026-01-01', 'period_end' => '2026-01-31'],
            ['period_start' => '2026-02-01', 'period_end' => '2026-02-28'],
            ['period_start' => '2026-03-01', 'period_end' => '2026-03-31'],
        ))
        ->create();

    $response = $this->getJson('/api/v1/qvct/indicators');
    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(3);
});

it('rh can update manual fields via API', function (): void {
    $rh = actingAsApiRole('rh');
    $indicator = QvctIndicator::factory()->forStructure($rh->structure)->create();

    $response = $this->putJson("/api/v1/qvct/indicators/{$indicator->id}", [
        'absenteeism_rate' => 5.5,
        'turnover_rate' => 8.0,
        'work_accidents_count' => 1,
        'notes' => 'Mai 2026 — situation stable.',
    ]);

    $response->assertSuccessful();
    $fresh = $indicator->fresh();
    expect((float) $fresh->absenteeism_rate)->toBe(5.5)
        ->and((float) $fresh->turnover_rate)->toBe(8.0)
        ->and($fresh->work_accidents_count)->toBe(1)
        ->and($fresh->captured_by)->toBe($rh->id);
});

it('intervenant cannot list indicators via API', function (): void {
    actingAsApiRole('intervenant');

    $this->getJson('/api/v1/qvct/indicators')->assertForbidden();
});

it('rh can trigger an on-demand snapshot via API', function (): void {
    actingAsApiRole('rh');

    $response = $this->postJson('/api/v1/qvct/indicators/snapshot');
    $response->assertCreated();
    expect(QvctIndicator::count())->toBe(1);
});
