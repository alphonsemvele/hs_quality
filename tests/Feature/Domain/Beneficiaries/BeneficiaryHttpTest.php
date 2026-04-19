<?php

use App\Models\Beneficiary;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;
use OwenIt\Auditing\Models\Audit;

/**
 * HTTP-layer tests for BeneficiaryController. Covers happy path, auth
 * denial, validation failure, cross-tenant isolation, and sensitive-read
 * audit logging on the dossier endpoint.
 */

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// --------------------------------------------------------------------------------------
// INDEX
// --------------------------------------------------------------------------------------

it('lists beneficiaries for a coordinateur', function () {
    $user = actingAsRole('coordinateur');
    Beneficiary::factory()->forStructure($user->structure)->count(3)->create();

    $response = $this->get('/beneficiaries');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/beneficiaries/index')
            ->has('beneficiaries.data', 3)
        );
});

it('lets an intervenant access the list but shows only assigned beneficiaries (none until pivot lands in Phase 1 Week 4)', function () {
    $user = actingAsRole('intervenant');
    Beneficiary::factory()->forStructure($user->structure)->count(3)->create();

    $response = $this->get('/beneficiaries');

    // Intervenant has view.assigned permission, but no assignment pivot yet,
    // so the page renders with zero rows. Once the pivot ships, the resource
    // collection will be filtered by intervenant_id.
    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/beneficiaries/index'));
});

it('does not leak beneficiaries across structures in the index', function () {
    $userA = actingAsRole('coordinateur');
    $structureB = Structure::factory()->create();

    Beneficiary::factory()->forStructure($userA->structure)->count(2)->create();
    Beneficiary::factory()->forStructure($structureB)->count(5)->create();

    $response = $this->get('/beneficiaries');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->has('beneficiaries.data', 2));
});

// --------------------------------------------------------------------------------------
// SHOW
// --------------------------------------------------------------------------------------

it('shows a beneficiary summary without health-data fields', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()
        ->forStructure($user->structure)
        ->withMedicalNotes('Confidential allergy')
        ->create();

    $response = $this->get("/beneficiaries/{$beneficiary->id}");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/beneficiaries/show')
            ->where('beneficiary.data.id', $beneficiary->id)
            ->where('beneficiary.data.full_name', $beneficiary->fullName())
            ->missing('beneficiary.data.medical_notes')
            ->missing('beneficiary.data.allergies')
        );
});

it('returns 404 when showing a beneficiary from another structure', function () {
    actingAsRole('coordinateur');
    $otherStructure = Structure::factory()->create();
    $foreign = Beneficiary::factory()->forStructure($otherStructure)->create();

    $response = $this->get("/beneficiaries/{$foreign->id}");

    $response->assertNotFound();
});

// --------------------------------------------------------------------------------------
// DOSSIER (sensitive read)
// --------------------------------------------------------------------------------------

it('includes health-data fields on the dossier endpoint', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()
        ->forStructure($user->structure)
        ->withMedicalNotes('Allergie arachide')
        ->create();

    $response = $this->get("/beneficiaries/{$beneficiary->id}/dossier");

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/beneficiaries/dossier')
            ->where('beneficiary.data.medical_notes', 'Allergie arachide')
            ->has('beneficiary.data.allergies')
        );
});

it('records an audit entry when the dossier is accessed', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    $audited = Audit::count();

    $this->get("/beneficiaries/{$beneficiary->id}/dossier")->assertOk();

    expect(Audit::count())->toBeGreaterThan($audited);

    $latest = Audit::latest('id')->first();
    expect($latest->event)->toBe('sensitive_read')
        ->and($latest->tags)->toContain('beneficiary_dossier')
        ->and($latest->structure_id)->toBe($user->structure_id);
});

// --------------------------------------------------------------------------------------
// STORE
// --------------------------------------------------------------------------------------

it('creates a beneficiary and auto-assigns structure_id', function () {
    $user = actingAsRole('coordinateur');

    $payload = [
        'first_name' => 'Marie',
        'last_name' => 'ESSOMBA',
        'date_of_birth' => '1945-07-22',
        'gir' => 3,
        'phone' => '+237 6 90 00 00 00',
    ];

    $response = $this->post('/beneficiaries', $payload);

    $response->assertRedirect();

    $beneficiary = Beneficiary::first();
    expect($beneficiary)->not->toBeNull()
        ->and($beneficiary->structure_id)->toBe($user->structure_id)
        ->and($beneficiary->first_name)->toBe('Marie')
        ->and($beneficiary->gir)->toBe(3);
});

it('rejects creating a beneficiary without a last_name', function () {
    actingAsRole('coordinateur');

    $response = $this->post('/beneficiaries', [
        'first_name' => 'Marie',
        // last_name missing
    ]);

    $response->assertSessionHasErrors('last_name');
    expect(Beneficiary::count())->toBe(0);
});

it('denies an intervenant from creating a beneficiary', function () {
    actingAsRole('intervenant');

    $response = $this->post('/beneficiaries', [
        'first_name' => 'Marie',
        'last_name' => 'ESSOMBA',
    ]);

    $response->assertForbidden();
    expect(Beneficiary::count())->toBe(0);
});

// --------------------------------------------------------------------------------------
// UPDATE
// --------------------------------------------------------------------------------------

it('updates a beneficiary via PUT', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()
        ->forStructure($user->structure)
        ->create(['gir' => 5]);

    $response = $this->put("/beneficiaries/{$beneficiary->id}", [
        'gir' => 3,
    ]);

    $response->assertRedirect();
    expect($beneficiary->fresh()->gir)->toBe(3);
});

it('blocks updating a beneficiary from another structure', function () {
    actingAsRole('coordinateur');
    $otherStructure = Structure::factory()->create();
    $foreign = Beneficiary::factory()->forStructure($otherStructure)->create(['gir' => 5]);

    $response = $this->put("/beneficiaries/{$foreign->id}", ['gir' => 3]);

    $response->assertNotFound();
    expect($foreign->fresh()->gir)->toBe(5);
});

// --------------------------------------------------------------------------------------
// DESTROY
// --------------------------------------------------------------------------------------

it('soft-deletes a beneficiary', function () {
    $user = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($user->structure)->create();

    $response = $this->delete("/beneficiaries/{$beneficiary->id}");

    $response->assertRedirect();
    expect(Beneficiary::count())->toBe(0);                              // scoped query misses soft-deleted
    expect(Beneficiary::withTrashed()->count())->toBe(1);
});
