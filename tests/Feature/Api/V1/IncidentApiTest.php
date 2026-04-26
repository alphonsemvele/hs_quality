<?php

use App\Enums\CategorieIncident;
use App\Enums\GraviteIncident;
use App\Models\Beneficiary;
use App\Models\Incident;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Queue::fake();
});

it('returns a list of incidents for the authenticated structure', function () {
    $coord = actingAsApiRole('coordinateur');

    Incident::factory()->count(3)->create(['structure_id' => $coord->structure_id]);

    $this->getJson('/api/v1/incidents')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('requires authentication to list incidents', function () {
    $this->getJson('/api/v1/incidents')->assertUnauthorized();
});

it('declares an incident with auto-classified gravity', function () {
    $coord = actingAsApiRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();

    $response = $this->postJson('/api/v1/incidents', [
        'occurred_at' => now()->subHour()->toISOString(),
        'categorie' => CategorieIncident::Chute->value,
        'description' => 'Le bénéficiaire est tombé dans la cuisine.',
        'lieu' => 'Cuisine',
        'avec_deces' => false,
        'avec_hospitalisation' => false,
        'avec_blessure_physique' => true,
        'beneficiary_id' => $beneficiary->id,
    ])->assertCreated();

    $response->assertJsonPath('gravite', GraviteIncident::Significatif->value);
    $response->assertJsonPath('statut', 'declare');
    $response->assertJsonPath('structure_id', $coord->structure_id);
});

it('classifies a fatal incident as critique', function () {
    actingAsApiRole('coordinateur');

    $this->postJson('/api/v1/incidents', [
        'occurred_at' => now()->subMinutes(30)->toISOString(),
        'categorie' => CategorieIncident::Chute->value,
        'description' => 'Décès constaté sur place.',
        'avec_deces' => true,
        'avec_hospitalisation' => false,
        'avec_blessure_physique' => false,
    ])
        ->assertCreated()
        ->assertJsonPath('gravite', GraviteIncident::Critique->value);
});

it('returns 422 when required incident fields are missing', function () {
    actingAsApiRole('coordinateur');

    $this->postJson('/api/v1/incidents', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['occurred_at', 'categorie', 'description']);
});

it('shows a single incident', function () {
    $coord = actingAsApiRole('coordinateur');
    $incident = Incident::factory()->create(['structure_id' => $coord->structure_id]);

    $this->getJson("/api/v1/incidents/{$incident->id}")
        ->assertOk()
        ->assertJsonPath('id', $incident->id);
});

it('cannot view an incident from another structure', function () {
    actingAsApiRole('coordinateur');

    $otherStructure = Structure::factory()->create();
    app()->instance('current_structure', $otherStructure); // temporarily switch

    $foreignIncident = Incident::factory()->create(['structure_id' => $otherStructure->id]);

    // Restore original structure before making the request
    $myStructure = User::find(auth()->id())->structure;
    app()->instance('current_structure', $myStructure);
    app(PermissionRegistrar::class)->setPermissionsTeamId($myStructure->getKey());

    $this->getJson("/api/v1/incidents/{$foreignIncident->id}")->assertNotFound();
});

it('does not leak incidents across structures in the list', function () {
    $ctx = twoStructures();

    app()->instance('current_structure', $ctx['structureA']);
    app(PermissionRegistrar::class)->setPermissionsTeamId($ctx['structureA']->getKey());
    test()->actingAs($ctx['userA'], 'sanctum');

    Incident::factory()->count(2)->create(['structure_id' => $ctx['structureA']->id]);
    Incident::factory()->count(3)->create(['structure_id' => $ctx['structureB']->id]);

    $this->getJson('/api/v1/incidents')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});
