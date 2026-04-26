<?php

use App\Enums\InterventionStatus;
use App\Events\InterventionStatusChanged;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('fires InterventionStatusChanged when checking in', function () {
    Event::fake([InterventionStatusChanged::class]);

    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->planned()
        ->create();

    $this->post("/interventions/{$intervention->id}/checkin")->assertRedirect();

    Event::assertDispatched(InterventionStatusChanged::class, function ($event) use ($intervention) {
        return $event->intervention->id === $intervention->id
            && $event->newStatus === InterventionStatus::InProgress;
    });
});

it('fires InterventionStatusChanged when checking out', function () {
    Event::fake([InterventionStatusChanged::class]);

    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->inProgress()
        ->create();

    $this->post("/interventions/{$intervention->id}/checkout", ['report_text' => 'OK'])->assertRedirect();

    Event::assertDispatched(InterventionStatusChanged::class, function ($event) use ($intervention) {
        return $event->intervention->id === $intervention->id
            && $event->newStatus === InterventionStatus::Completed;
    });
});

it('fires InterventionStatusChanged when cancelling', function () {
    Event::fake([InterventionStatusChanged::class]);

    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->planned()
        ->create();

    $this->post("/interventions/{$intervention->id}/cancel", [
        'cancellation_reason' => 'Bénéficiaire absent',
    ])->assertRedirect();

    Event::assertDispatched(InterventionStatusChanged::class, function ($event) use ($intervention) {
        return $event->intervention->id === $intervention->id
            && $event->newStatus === InterventionStatus::Cancelled;
    });
});

it('does not fire an event on a plain update', function () {
    Event::fake([InterventionStatusChanged::class]);

    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->create(['structure_id' => $coord->structure_id, 'type' => 'intervenant']);
    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->planned()
        ->create();

    $this->put("/interventions/{$intervention->id}", ['planned_date' => '2026-07-01'])->assertRedirect();

    Event::assertNotDispatched(InterventionStatusChanged::class);
});
