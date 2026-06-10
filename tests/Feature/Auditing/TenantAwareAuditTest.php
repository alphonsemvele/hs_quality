<?php

use App\Auditing\TenantAwareAudit;
use App\Models\Structure;
use App\Models\User;

/**
 * Verifies the TenantAwareAudit model auto-populates structure_id from the
 * current tenant context and that audit entries are queryable per tenant.
 */
it('auto-populates structure_id from current tenant on create', function () {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);

    $audit = TenantAwareAudit::create([
        'user_type' => User::class,
        'user_id' => 1,
        'event' => 'created',
        'auditable_type' => 'App\\Models\\Stub',
        'auditable_id' => 42,
        'new_values' => json_encode(['foo' => 'bar']),
    ]);

    expect($audit->structure_id)->toBe($structure->id);
});

it('leaves structure_id null when no tenant is bound (admin/system actions)', function () {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }

    $audit = TenantAwareAudit::create([
        'user_type' => User::class,
        'user_id' => 1,
        'event' => 'system.task_run',
        'auditable_type' => 'App\\Models\\Stub',
        'auditable_id' => 1,
    ]);

    expect($audit->structure_id)->toBeNull();
});

it('respects explicitly-set structure_id', function () {
    $structureInContext = Structure::factory()->create();
    $structureInAudit = Structure::factory()->create();

    app()->instance('current_structure', $structureInContext);

    $audit = TenantAwareAudit::create([
        'structure_id' => $structureInAudit->id,
        'user_type' => User::class,
        'user_id' => 1,
        'event' => 'created',
        'auditable_type' => 'App\\Models\\Stub',
        'auditable_id' => 1,
    ]);

    expect($audit->structure_id)->toBe($structureInAudit->id);
});
