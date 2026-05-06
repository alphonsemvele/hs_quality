<?php

use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Verifies the RoleSeeder builds the 6 personas × permissions matrix per
 * references/rbac/matrix.md and references/rbac/seeding-patterns.md.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('creates the 6 personas', function () {
    $roleNames = Role::pluck('name')->sort()->values()->all();

    expect($roleNames)->toBe([
        'beneficiaire_portal',
        'coordinateur',
        'dirigeant',
        'intervenant',
        'referent_qualite',
        'rh',
    ]);
});

it('seeds all permissions in the catalog', function () {
    expect(Permission::count())->toBeGreaterThanOrEqual(55);
});

it('gives an intervenant only their own-record permissions', function () {
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    $role = Role::findByName('intervenant', 'web');

    $names = $role->permissions->pluck('name')->all();

    expect($names)
        ->toContain('interventions.view.own', 'incidents.declare', 'qvct.respond')
        ->not->toContain(
            'interventions.view.structure',
            'incidents.analyze',
            'incidents.close',
            'cross_tenant_benchmark.read',
        );
});

it('gives a coordinateur team-level operational access', function () {
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    $role = Role::findByName('coordinateur', 'web');
    $names = $role->permissions->pluck('name')->all();

    expect($names)
        ->toContain(
            'incidents.analyze',
            'incidents.close',
            'beneficiaries.update',
            'qvct.view.team_aggregates',
        )
        ->not->toContain('cross_tenant_benchmark.read');
});

it('gives a dirigeant executive + admin permissions including erasure', function () {
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    $role = Role::findByName('dirigeant', 'web');
    $names = $role->permissions->pluck('name')->all();

    expect($names)
        ->toContain(
            'dashboard.executive.view',
            'rgpd.erasure.execute',
            'users.manage.structure',
            'structure.configure',
            // Dirigeant must be able to fully operate the structure: plan
            // and delete interventions, declare incidents. Without these
            // the UI shows the buttons (per the Inertia abilities matrix)
            // but every click 403s.
            'interventions.update.team',
            'interventions.delete',
            'incidents.declare',
        )
        ->not->toContain('cross_tenant_benchmark.read');
});

it('never grants cross_tenant_benchmark.read to any human role', function () {
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    foreach (Role::all() as $role) {
        expect($role->permissions->pluck('name')->all())
            ->not->toContain('cross_tenant_benchmark.read');
    }
});

it('restricts beneficiaire_portal to portal actions only', function () {
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    $role = Role::findByName('beneficiaire_portal', 'web');
    $names = $role->permissions->pluck('name')->all();

    $allowedPrefixes = ['portal.', 'audit_logs.view.own', 'rgpd.erasure.request'];

    foreach ($names as $name) {
        $allowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                $allowed = true;
                break;
            }
        }
        expect($allowed)->toBeTrue("Permission {$name} should not be on beneficiaire_portal");
    }
});
