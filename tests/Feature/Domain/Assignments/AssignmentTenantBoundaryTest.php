<?php

declare(strict_types=1);

use App\Models\Beneficiary;
use App\Models\IntervenantAssignment;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Wave 0 / H6 — AssignmentController::store must NOT attach an intervenant
 * from a different structure even if AttachIntervenantRequest's scoping rule
 * is later relaxed. The controller now queries User scoped to the
 * beneficiary's structure_id before findOrFail.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('rejects attaching an intervenant from another structure to a local beneficiary', function (): void {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();

    $foreignStructure = Structure::factory()->create();
    $foreignIntervenant = User::factory()->forStructure($foreignStructure)->intervenant()->create();

    $response = $this->from("/beneficiaries/{$beneficiary->id}")->post(
        "/beneficiaries/{$beneficiary->id}/assignments",
        ['intervenant_id' => $foreignIntervenant->id],
    );

    expect($response->status())->toBeIn([302, 404]);
    expect(IntervenantAssignment::query()->where('beneficiary_id', $beneficiary->id)->exists())
        ->toBeFalse();
});
