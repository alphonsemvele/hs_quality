<?php

/*
|--------------------------------------------------------------------------
| Test Case & Global Helpers
|--------------------------------------------------------------------------
|
| The closest test suite file to each test file is auto-discovered. Apply
| RefreshDatabase to every Feature test (they may hit the DB). Unit tests
| stay isolated (no base class → no DB wiring cost).
|
| Global helpers used across all tests:
|   - twoStructures()    — bootstraps 2 tenants + users for cross-tenant leak tests
|   - actingAsStructure() — authenticates as a user + binds tenant context
|   - actingAsRole()      — (added Phase 0 Week 3 Day 3 when Spatie Permission is wired)
|
| See: references/tenancy/testing.md
|      references/rbac/test-helpers.md
|
*/

use App\Models\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit');

/**
 * Bootstraps two fresh tenants and returns an array of useful handles for
 * cross-tenant leak tests. Each structure has a coordinateur-level user
 * (role assignment is added once Spatie Permission is wired — Week 3 Day 3).
 */
function twoStructures(): array
{
    $structureA = Structure::factory()->create(['nom' => 'Structure A', 'code' => 'TEST-A']);
    $structureB = Structure::factory()->create(['nom' => 'Structure B', 'code' => 'TEST-B']);

    $userA = User::factory()->create(['service_id' => null]);
    $userA->forceFill(['structure_id' => $structureA->id])->save();

    $userB = User::factory()->create(['service_id' => null]);
    $userB->forceFill(['structure_id' => $structureB->id])->save();

    return [
        'structureA' => $structureA,
        'structureB' => $structureB,
        'userA' => $userA,
        'userB' => $userB,
    ];
}

/**
 * Acts as a user + binds the tenant context so BelongsToStructure and
 * Spatie Permission work in the test. Returns the acting user for
 * chaining / assertions.
 */
function actingAsStructure(User $user): User
{
    $structure = $user->structure ?? Structure::find($user->structure_id);
    abort_if($structure === null, 500, 'User has no structure — cannot act as.');

    app()->instance('current_structure', $structure);
    test()->actingAs($user);

    return $user;
}
