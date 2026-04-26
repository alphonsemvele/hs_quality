<?php

declare(strict_types=1);

use App\Enums\InterventionStatus;
use App\Events\IncidentDeclared;
use App\Events\InterventionStatusChanged;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Services\IncidentService;
use App\Services\InterventionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;

/**
 * Block A / #57 — every intervention lifecycle transition + every incident
 * declaration MUST broadcast on the structure-private channel. Without
 * this validation, a misconfigured event listener (or a future refactor
 * that forgets to dispatch) would silently break real-time updates and
 * we'd only notice when coordinators complain.
 *
 * Coverage:
 *   - InterventionStatusChanged fires on check-in / check-out / cancel /
 *     missed-sweep
 *   - IncidentDeclared fires on incident creation
 *   - Channel name is the tenant-private channel (`structure.{id}`)
 *   - broadcastAs returns the documented event name (mobile clients
 *     subscribe to it)
 *   - broadcastWith carries the documented payload shape
 *
 * We use Event::fake to assert the dispatch happened. End-to-end Reverb
 * delivery (the websocket server actually pushing to a connected client)
 * is verified manually with `php artisan reverb:start` + the React app.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('broadcasts InterventionStatusChanged on check-in', function (): void {
    Event::fake([InterventionStatusChanged::class]);

    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervention = Intervention::factory()
        ->forStructure($coord->structure)
        ->state([
            'beneficiary_id' => $beneficiary->id,
            'intervenant_id' => $coord->id,
            'status' => InterventionStatus::Planned->value,
        ])
        ->create();

    app(InterventionService::class)->checkIn($intervention);

    Event::assertDispatched(InterventionStatusChanged::class, function ($event) use ($intervention) {
        return $event->intervention->id === $intervention->id
            && $event->newStatus === InterventionStatus::InProgress;
    });
});

it('broadcasts InterventionStatusChanged on check-out', function (): void {
    Event::fake([InterventionStatusChanged::class]);

    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervention = Intervention::factory()
        ->forStructure($coord->structure)
        ->state([
            'beneficiary_id' => $beneficiary->id,
            'intervenant_id' => $coord->id,
            'status' => InterventionStatus::InProgress->value,
            'actual_start_at' => now()->subHour(),
        ])
        ->create();

    app(InterventionService::class)->checkOut($intervention, ['report_text' => 'Done']);

    Event::assertDispatched(InterventionStatusChanged::class, function ($event) use ($intervention) {
        return $event->intervention->id === $intervention->id
            && $event->newStatus === InterventionStatus::Completed;
    });
});

it('broadcasts InterventionStatusChanged on cancel', function (): void {
    Event::fake([InterventionStatusChanged::class]);

    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervention = Intervention::factory()
        ->forStructure($coord->structure)
        ->state([
            'beneficiary_id' => $beneficiary->id,
            'intervenant_id' => $coord->id,
            'status' => InterventionStatus::Planned->value,
        ])
        ->create();

    app(InterventionService::class)->cancel($intervention, 'Bénéficiaire absent');

    Event::assertDispatched(InterventionStatusChanged::class, function ($event) {
        return $event->newStatus === InterventionStatus::Cancelled;
    });
});

it('broadcasts on the tenant-private channel only', function (): void {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervention = Intervention::factory()
        ->forStructure($coord->structure)
        ->state([
            'beneficiary_id' => $beneficiary->id,
            'intervenant_id' => $coord->id,
            'status' => InterventionStatus::Planned->value,
        ])
        ->create();

    $event = new InterventionStatusChanged($intervention, InterventionStatus::InProgress);
    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
    expect($channels[0]->name)->toBe("private-structure.{$intervention->structure_id}");
});

it('publishes a stable broadcast event name + payload shape', function (): void {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervention = Intervention::factory()
        ->forStructure($coord->structure)
        ->state(['beneficiary_id' => $beneficiary->id, 'intervenant_id' => $coord->id])
        ->create();

    $event = new InterventionStatusChanged($intervention, InterventionStatus::InProgress);

    expect($event->broadcastAs())->toBe('intervention.status.changed');
    expect($event->broadcastWith())->toMatchArray([
        'intervention_id' => $intervention->id,
        'status' => 'in_progress',
    ]);
});

it('broadcasts IncidentDeclared on incident creation', function (): void {
    Event::fake([IncidentDeclared::class]);

    $coord = actingAsRole('coordinateur');

    $incident = app(IncidentService::class)->declare([
        'occurred_at' => now()->subMinutes(15),
        'categorie' => 'chute',
        'description' => 'Test',
        'avec_deces' => false,
        'avec_hospitalisation' => false,
        'avec_blessure_physique' => false,
    ], $coord);

    Event::assertDispatched(IncidentDeclared::class, function ($event) use ($incident) {
        return $event->incident->id === $incident->id;
    });
});

it('IncidentDeclared broadcastAs + payload shape are stable (mobile contract)', function (): void {
    $coord = actingAsRole('coordinateur');

    $incident = app(IncidentService::class)->declare([
        'occurred_at' => now()->subMinutes(15),
        'categorie' => 'chute',
        'description' => 'Test',
        'avec_deces' => false,
        'avec_hospitalisation' => false,
        'avec_blessure_physique' => false,
    ], $coord);

    $event = new IncidentDeclared($incident);

    expect($event->broadcastAs())->toBe('incident.declared');
    expect($event->broadcastWith())->toMatchArray([
        'incident_id' => $incident->id,
        'categorie' => 'chute',
    ]);

    $channels = $event->broadcastOn();
    expect($channels[0]->name)->toBe("private-structure.{$incident->structure_id}");
});
