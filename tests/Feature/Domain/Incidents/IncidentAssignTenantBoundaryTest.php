<?php

declare(strict_types=1);

use App\Models\Incident;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Wave 0 / H6 — IncidentController::assign must NOT successfully assign an
 * incident to a coordinator from another structure, even if a regression
 * loosens the AssignIncidentRequest scoping rule.
 *
 * Defense-in-depth: the controller now queries User scoped to
 * $incident->structure_id before findOrFail. If a future change removes the
 * Rule::exists structure clause, the controller still 404s.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('rejects assigning an incident to a coordinator from another structure', function (): void {
    $coord = actingAsRole('coordinateur');
    $foreignStructure = Structure::factory()->create();
    $foreignCoord = User::factory()->forStructure($foreignStructure)->coordinateur()->create();

    $incident = Incident::factory()->forStructure($coord->structure)->create();

    $response = $this->from("/incidents/{$incident->id}")->post("/incidents/{$incident->id}/assign", [
        'coordinateur_id' => $foreignCoord->id,
    ]);

    // Web layer either redirects back with validation errors (form-request
    // rejects → 302) or 404s (controller's tenant-bound findOrFail fails).
    // What matters is the assignment did not succeed.
    expect($response->status())->toBeIn([302, 404]);
    expect($incident->fresh()->assigned_to)->toBeNull();
});
