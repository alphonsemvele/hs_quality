<?php

use App\Models\Beneficiary;
use App\Models\Incident;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('returns empty groups for a short query', function () {
    actingAsRole('coordinateur');

    $response = $this->getJson('/search/quick?q=a');

    $response->assertSuccessful()
        ->assertExactJson(['groups' => []]);
});

it('returns empty groups for an empty query', function () {
    actingAsRole('coordinateur');

    $this->getJson('/search/quick?q=')
        ->assertSuccessful()
        ->assertExactJson(['groups' => []]);
});

it('finds beneficiaries matching the query', function () {
    $coord = actingAsRole('coordinateur');

    Beneficiary::factory()->forStructure($coord->structure)->create([
        'first_name' => 'Sophie',
        'last_name' => 'Bernard',
    ]);
    Beneficiary::factory()->forStructure($coord->structure)->create([
        'first_name' => 'Lucas',
        'last_name' => 'Martin',
    ]);

    $response = $this->getJson('/search/quick?q=Bernard');

    $response->assertSuccessful();
    $groups = $response->json('groups');

    expect($groups)->not->toBeEmpty();
    $benefGroup = collect($groups)->firstWhere('key', 'beneficiaries');
    expect($benefGroup)->not->toBeNull()
        ->and($benefGroup['items'])->toHaveCount(1)
        ->and($benefGroup['items'][0]['title'])->toBe('Sophie Bernard');
});

it('respects tenant isolation', function () {
    $coord = actingAsRole('coordinateur');
    $other = Structure::factory()->create();

    Beneficiary::factory()->forStructure($coord->structure)->create([
        'first_name' => 'Local',
        'last_name' => 'Tenant',
    ]);
    Beneficiary::factory()->forStructure($other)->create([
        'first_name' => 'Other',
        'last_name' => 'Tenant',
    ]);

    $response = $this->getJson('/search/quick?q=Tenant');

    $response->assertSuccessful();
    $items = collect($response->json('groups'))
        ->firstWhere('key', 'beneficiaries')['items'] ?? [];

    expect($items)->toHaveCount(1)
        ->and($items[0]['title'])->toBe('Local Tenant');
});

it('hides ability-gated groups for personas without permission', function () {
    // Intervenant has access to interventions.view (own) but not incidents.view
    // in most ability matrices. We don't assert specific abilities; we assert
    // that the response only contains groups the persona is allowed to see.
    $intervenant = actingAsRole('intervenant');

    Incident::factory()->forStructure($intervenant->structure)->create([
        'description' => 'Matching keyword inside an incident',
    ]);

    $response = $this->getJson('/search/quick?q=Matching');

    $response->assertSuccessful();
    $groups = $response->json('groups');
    $incidentsGroup = collect($groups)->firstWhere('key', 'incidents');

    // Intervenants do not surface the incidents search bucket in the palette.
    expect($incidentsGroup)->toBeNull();
});

it('rejects unauthenticated calls', function () {
    $this->get('/search/quick?q=anything')->assertRedirect('/login');
});
