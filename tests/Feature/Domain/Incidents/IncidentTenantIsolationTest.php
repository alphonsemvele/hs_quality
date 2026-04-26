<?php

use App\Models\Incident;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('cannot query incidents across tenant boundaries', function () {
    $tenants = twoStructures();
    $structureA = $tenants['structureA'];
    $structureB = $tenants['structureB'];

    $userA = User::factory()->create(['structure_id' => $structureA->id]);
    $userB = User::factory()->create(['structure_id' => $structureB->id]);

    Incident::factory()->forStructure($structureA)->create(['declared_by' => $userA->id]);
    Incident::factory()->forStructure($structureB)->create(['declared_by' => $userB->id]);

    app()->instance('current_structure', $structureA);

    $results = Incident::all();
    expect($results)->toHaveCount(1)
        ->and($results->first()->structure_id)->toBe($structureA->id);
});

it('returns 404 when accessing another structure incident via route model binding', function () {
    $coord = actingAsRole('coordinateur');

    $other = Structure::factory()->create();
    $otherUser = User::factory()->create(['structure_id' => $other->id]);
    $foreignIncident = Incident::factory()->forStructure($other)->create([
        'declared_by' => $otherUser->id,
    ]);

    $this->get("/incidents/{$foreignIncident->id}")->assertNotFound();
    $this->put("/incidents/{$foreignIncident->id}", ['description' => 'test'])->assertNotFound();
    $this->delete("/incidents/{$foreignIncident->id}")->assertNotFound();
});
