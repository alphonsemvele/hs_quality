<?php

/*
|--------------------------------------------------------------------------
| Test Case & Global Helpers
|--------------------------------------------------------------------------
|
| RefreshDatabase on Feature tests. Unit tests stay isolated.
|
| Helpers:
|   - twoStructures()       — bootstraps 2 tenants + users for leak tests
|   - actingAsStructure()   — acts as a user and binds their tenant context
|   - actingAsRole()        — creates a user + structure + assigns a role
|
| See: references/tenancy/testing.md + references/rbac/test-helpers.md
*/

use App\Models\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Inertia renders a Blade view that loads the Vite manifest; in tests
        // we have no built assets, so we tell the framework to skip Vite
        // entirely. assertInertia()/JSON inspection still works.
        test()->withoutVite();

        // Backend tests assert Inertia component names + props but don't
        // require the actual .tsx files to exist. Front-end work happens
        // separately; the backend ships the contract independently.
        config(['inertia.testing.ensure_pages_exist' => false]);
    })
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/**
 * Bootstraps two fresh tenants and returns handles for cross-tenant leak tests.
 * Each structure has a coordinateur-type user pre-assigned to the role
 * (requires RoleSeeder to have run; tests using this helper should seed first).
 */
function twoStructures(): array
{
    $structureA = Structure::factory()->create(['name' => 'Structure A', 'code' => 'TEST-A']);
    $structureB = Structure::factory()->create(['name' => 'Structure B', 'code' => 'TEST-B']);

    $userA = User::factory()->forStructure($structureA)->coordinateur()->create();
    $userB = User::factory()->forStructure($structureB)->coordinateur()->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($structureA->getKey());
    $userA->assignRole('coordinateur');

    app(PermissionRegistrar::class)->setPermissionsTeamId($structureB->getKey());
    $userB->assignRole('coordinateur');

    return [
        'structureA' => $structureA,
        'structureB' => $structureB,
        'userA' => $userA,
        'userB' => $userB,
    ];
}

/**
 * Acts as a user + binds their tenant context so BelongsToStructure +
 * Spatie Permission team_id work in tests.
 */
function actingAsStructure(User $user): User
{
    $structure = $user->structure ?? Structure::find($user->structure_id);
    abort_if($structure === null, 500, 'User has no structure — cannot act as.');

    app()->instance('current_structure', $structure);
    app(PermissionRegistrar::class)->setPermissionsTeamId($user->structure_id);

    test()->actingAs($user);

    return $user;
}

/**
 * Creates a user within a structure and assigns a Spatie role. Convenient
 * for role-scoped tests that don't care about the specific user identity.
 *
 * Usage:
 *   $user = actingAsRole('coordinateur');              // new structure
 *   $user = actingAsRole('intervenant', $structure);   // existing structure
 *   $user = actingAsRole('dirigeant', user: $u);        // existing user
 */
function actingAsRole(string $role, ?Structure $structure = null, ?User $user = null): User
{
    $structure ??= Structure::factory()->create();

    $user ??= User::factory()->forStructure($structure)->state(['type' => $role])->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());
    $user->assignRole($role);

    app()->instance('current_structure', $structure);
    test()->actingAs($user);

    return $user;
}

/**
 * Same as actingAsRole() but authenticates via the Sanctum guard for API tests.
 *
 * Must also pre-bind current_structure because SubstituteBindings (route model
 * binding) runs before TenantResolver in the API middleware stack, and
 * StructureScope returns 1=0 when no tenant is bound.
 */
function actingAsApiRole(string $role, ?Structure $structure = null, ?User $user = null): User
{
    $structure ??= Structure::factory()->create();

    $user ??= User::factory()->forStructure($structure)->state(['type' => $role])->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());
    $user->assignRole($role);

    app()->instance('current_structure', $structure);
    test()->actingAs($user, 'sanctum');

    return $user;
}
