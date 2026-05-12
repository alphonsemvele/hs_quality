<?php

use App\Enums\InterventionStatus;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('cancels multiple planned interventions in one request', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->forStructure($coord->structure)->state(['type' => 'intervenant'])->create();

    $interventions = collect(range(1, 3))->map(fn () => Intervention::factory()->forStructure($coord->structure)->create([
        'intervenant_id' => $intervenant->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => InterventionStatus::Planned->value,
    ]));

    $response = $this->post('/interventions/bulk/cancel', [
        'ids' => $interventions->pluck('id')->all(),
        'cancellation_reason' => 'Annulation en masse de test',
    ]);

    $response->assertRedirect();

    foreach ($interventions as $i) {
        $fresh = Intervention::query()->find($i->id);
        expect($fresh->status)->toBe(InterventionStatus::Cancelled);
        expect($fresh->cancellation_reason)->toBe('Annulation en masse de test');
    }
});

it('silently skips already-terminal interventions', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->forStructure($coord->structure)->state(['type' => 'intervenant'])->create();

    $planned = Intervention::factory()->forStructure($coord->structure)->create([
        'intervenant_id' => $intervenant->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => InterventionStatus::Planned->value,
    ]);
    $completed = Intervention::factory()->forStructure($coord->structure)->create([
        'intervenant_id' => $intervenant->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => InterventionStatus::Completed->value,
    ]);

    $response = $this->post('/interventions/bulk/cancel', [
        'ids' => [$planned->id, $completed->id],
        'cancellation_reason' => 'Mix terminal/planned',
    ]);

    $response->assertRedirect();
    expect(Intervention::query()->find($planned->id)->status)->toBe(InterventionStatus::Cancelled);
    // Completed stays completed — silently skipped, never errors.
    expect(Intervention::query()->find($completed->id)->status)->toBe(InterventionStatus::Completed);
});

it('rejects bulk cancel without cancellation_reason', function () {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->forStructure($coord->structure)->state(['type' => 'intervenant'])->create();

    $intervention = Intervention::factory()->forStructure($coord->structure)->create([
        'intervenant_id' => $intervenant->id,
        'beneficiary_id' => $beneficiary->id,
        'status' => InterventionStatus::Planned->value,
    ]);

    $this->post('/interventions/bulk/cancel', [
        'ids' => [$intervention->id],
    ])->assertSessionHasErrors('cancellation_reason');

    expect(Intervention::query()->find($intervention->id)->status)->toBe(InterventionStatus::Planned);
});

it('rejects bulk cancel with empty ids', function () {
    actingAsRole('coordinateur');

    $this->post('/interventions/bulk/cancel', [
        'ids' => [],
        'cancellation_reason' => 'Test',
    ])->assertSessionHasErrors('ids');
});

it('caps bulk cancel to 200 items', function () {
    actingAsRole('coordinateur');

    $this->post('/interventions/bulk/cancel', [
        'ids' => array_fill(0, 201, '019dfc2d-1a71-70a4-bfa9-797165f21f05'),
        'cancellation_reason' => 'Too many',
    ])->assertSessionHasErrors('ids');
});

it('refuses cross-tenant ids via tenant scope', function () {
    $coordA = actingAsRole('coordinateur');
    $structureB = Structure::factory()->create();
    $intervenantB = User::factory()->forStructure($structureB)->state(['type' => 'intervenant'])->create();
    $beneficiaryB = Beneficiary::factory()->forStructure($structureB)->create();
    $foreignIntervention = Intervention::factory()->forStructure($structureB)->create([
        'intervenant_id' => $intervenantB->id,
        'beneficiary_id' => $beneficiaryB->id,
        'status' => InterventionStatus::Planned->value,
    ]);

    // Even though the user pretends to act on the foreign id, the global
    // BelongsToStructure scope filters it out — the row stays Planned.
    $this->post('/interventions/bulk/cancel', [
        'ids' => [$foreignIntervention->id],
        'cancellation_reason' => 'Cross tenant attempt',
    ]);

    expect(Intervention::query()->withoutGlobalScopes()->find($foreignIntervention->id)->status)
        ->toBe(InterventionStatus::Planned);
});
