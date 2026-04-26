<?php

use App\Models\Beneficiary;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('returns a paginated list of beneficiaries', function () {
    $coord = actingAsApiRole('coordinateur');

    Beneficiary::factory()->forStructure($coord->structure)->count(5)->create();

    $this->getJson('/api/v1/beneficiaries')
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('requires authentication to list beneficiaries', function () {
    $this->getJson('/api/v1/beneficiaries')->assertUnauthorized();
});

it('returns a single beneficiary', function () {
    $coord = actingAsApiRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();

    $this->getJson("/api/v1/beneficiaries/{$beneficiary->id}")
        ->assertOk()
        ->assertJsonPath('id', $beneficiary->id)
        ->assertJsonStructure(['id', 'first_name', 'last_name', 'address', 'phone']);
});

it('returns 404 for a beneficiary from another structure', function () {
    actingAsApiRole('coordinateur');

    $otherStructure = Structure::factory()->create();
    app()->instance('current_structure', $otherStructure);
    $foreign = Beneficiary::factory()->forStructure($otherStructure)->create();

    $myStructure = User::find(auth()->id())->structure;
    app()->instance('current_structure', $myStructure);
    app(PermissionRegistrar::class)->setPermissionsTeamId($myStructure->getKey());

    $this->getJson("/api/v1/beneficiaries/{$foreign->id}")->assertNotFound();
});

it('does not leak beneficiaries across structures', function () {
    $ctx = twoStructures();

    app()->instance('current_structure', $ctx['structureA']);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ctx['structureA']->getKey());
    test()->actingAs($ctx['userA'], 'sanctum');

    Beneficiary::factory()->forStructure($ctx['structureA'])->count(3)->create();
    Beneficiary::factory()->forStructure($ctx['structureB'])->count(4)->create();

    $this->getJson('/api/v1/beneficiaries')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});
