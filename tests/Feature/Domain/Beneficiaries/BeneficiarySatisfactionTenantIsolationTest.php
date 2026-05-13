<?php

use App\Models\Beneficiary;
use App\Models\BeneficiarySatisfactionRating;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

/**
 * Cross-tenant leak guard — required for every new domain model
 * (see CLAUDE.md non-negotiable rule #3).
 *
 * Verifies that:
 *   - The Eloquent query scope hides ratings from a foreign structure
 *   - The HTTP endpoint returns 404 (not 403) on a foreign beneficiary
 *   - A direct POST against a foreign beneficiary cannot create a rating
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('hides satisfaction ratings from other structures via the Eloquent scope', function () {
    $userA = actingAsRole('coordinateur');
    $structureB = Structure::factory()->create();
    $beneficiaryA = Beneficiary::factory()->forStructure($userA->structure)->create();
    $beneficiaryB = Beneficiary::factory()->forStructure($structureB)->create();

    BeneficiarySatisfactionRating::factory()->forBeneficiary($beneficiaryA)->count(2)->create();
    BeneficiarySatisfactionRating::factory()->forBeneficiary($beneficiaryB)->count(5)->create();

    expect(BeneficiarySatisfactionRating::query()->count())->toBe(2);
});

it('returns 404 on a foreign tenants satisfaction page', function () {
    actingAsRole('coordinateur');
    $structureB = Structure::factory()->create();
    $beneficiaryB = Beneficiary::factory()->forStructure($structureB)->create();

    $this->get("/beneficiaries/{$beneficiaryB->id}/satisfaction")
        ->assertNotFound();
});

it('cannot create a satisfaction rating against a foreign tenants beneficiary', function () {
    actingAsRole('coordinateur');
    $structureB = Structure::factory()->create();
    $beneficiaryB = Beneficiary::factory()->forStructure($structureB)->create();

    $this->post("/beneficiaries/{$beneficiaryB->id}/satisfaction", [
        'score' => 5,
        'rated_at' => now()->toDateString(),
    ])->assertNotFound();

    expect(BeneficiarySatisfactionRating::query()->where('beneficiary_id', $beneficiaryB->id)->count())
        ->toBe(0);
});
