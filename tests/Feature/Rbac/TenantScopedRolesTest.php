<?php

use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Verifies Spatie Permission teams feature with team_foreign_key = structure_id
 * correctly scopes role assignments per tenant. A user assigned a role in
 * structure A does NOT have that role when queried from structure B's context.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('assigns a role within a specific structure', function () {
    $structure = Structure::factory()->create();
    $user = User::factory()->forStructure($structure)->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->id);
    $user->assignRole('coordinateur');

    expect($user->hasRole('coordinateur'))->toBeTrue()
        ->and($user->hasPermissionTo('incidents.analyze'))->toBeTrue()
        ->and($user->hasPermissionTo('interventions.view.own'))->toBeFalse();
});

it('does not leak role assignments across structures', function () {
    $structureA = Structure::factory()->create();
    $structureB = Structure::factory()->create();

    $userA = User::factory()->forStructure($structureA)->create();

    // Assign 'coordinateur' in structure A's context
    app(PermissionRegistrar::class)->setPermissionsTeamId($structureA->id);
    $userA->assignRole('coordinateur');
    expect($userA->hasRole('coordinateur'))->toBeTrue();

    // Switch to structure B's context — userA's role should not appear here
    app(PermissionRegistrar::class)->setPermissionsTeamId($structureB->id);
    $userA->unsetRelations();  // clear eager-loaded roles so fresh check runs
    expect($userA->hasRole('coordinateur'))->toBeFalse();
});

it('actingAsRole helper sets up role + structure + tenant context end-to-end', function () {
    $user = actingAsRole('coordinateur');

    expect($user->hasRole('coordinateur'))->toBeTrue()
        ->and($user->hasPermissionTo('incidents.analyze'))->toBeTrue()
        ->and(currentStructure())->not->toBeNull()
        ->and(currentStructure()->id)->toBe($user->structure_id);
});

it('actingAsRole with different roles gives different permissions', function () {
    $intervenant = actingAsRole('intervenant');
    expect($intervenant->hasPermissionTo('incidents.declare'))->toBeTrue()
        ->and($intervenant->hasPermissionTo('incidents.analyze'))->toBeFalse();

    // New call creates a fresh user in a fresh structure
    $dirigeant = actingAsRole('dirigeant');
    expect($dirigeant->hasPermissionTo('dashboard.executive.view'))->toBeTrue()
        ->and($dirigeant->hasPermissionTo('rgpd.erasure.execute'))->toBeTrue();
});
