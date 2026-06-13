<?php

declare(strict_types=1);

use App\Models\Structure;
use App\Models\User;
use App\Services\SuperAdminImpersonationService;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit coverage for the session-backed impersonation service. RefreshDatabase
 * is reused because we exercise structure lookups (the service falls back to
 * a DB query when its memoised handle has been invalidated by deletion).
 *
 * These tests live in tests/Unit because the public API is small and the
 * behaviour matters per-method — feature coverage (controller, middleware,
 * Inertia props, audit stamping) lives in
 * tests/Feature/Admin/SuperAdminImpersonationTest.php.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('is inactive when the session contains no impersonation keys', function (): void {
    expect(app(SuperAdminImpersonationService::class)->isActive())->toBeFalse();
});

it('records structure_id and started_at on start()', function (): void {
    $structure = Structure::factory()->create();
    $service = app(SuperAdminImpersonationService::class);

    $service->start($structure);

    expect($service->isActive())->toBeTrue();
    expect($service->structureId())->toBe((string) $structure->getKey());
    expect($service->startedAt())->toBeInstanceOf(CarbonImmutable::class);
});

it('flushes state on stop()', function (): void {
    $structure = Structure::factory()->create();
    $service = app(SuperAdminImpersonationService::class);

    $service->start($structure);
    $service->stop();

    expect($service->isActive())->toBeFalse();
    expect($service->structureId())->toBeNull();
    expect($service->structure())->toBeNull();
});

it('auto-deactivates when the underlying structure has been deleted', function (): void {
    $structure = Structure::factory()->create();
    app(SuperAdminImpersonationService::class)->start($structure);

    // Drop the row from underneath the session — simulates a structure
    // deleted by another operator while a super-admin still holds an
    // active impersonation handle. Reset the in-container instance so
    // the next resolve gets a service with no memoised structure.
    Structure::query()->whereKey($structure->getKey())->delete();
    app()->forgetInstance(SuperAdminImpersonationService::class);

    $service = app(SuperAdminImpersonationService::class);

    expect($service->structure())->toBeNull();
    expect($service->isActive())->toBeFalse();
});

it('returns the impersonator id for an active super_admin session', function (): void {
    $admin = User::factory()->create([
        'structure_id' => null,
        'is_platform_admin' => true,
    ]);
    $structure = Structure::factory()->create();
    $service = app(SuperAdminImpersonationService::class);

    $service->start($structure);

    expect($service->impersonatorId($admin))->toBe((int) $admin->getKey());
});

it('returns null impersonator id for a non-super_admin user', function (): void {
    $user = User::factory()->create(['is_platform_admin' => false]);
    $structure = Structure::factory()->create();
    $service = app(SuperAdminImpersonationService::class);

    $service->start($structure);

    expect($service->impersonatorId($user))->toBeNull();
});

it('returns null impersonator id when no session is active', function (): void {
    $admin = User::factory()->create([
        'structure_id' => null,
        'is_platform_admin' => true,
    ]);

    expect(app(SuperAdminImpersonationService::class)->impersonatorId($admin))->toBeNull();
});
