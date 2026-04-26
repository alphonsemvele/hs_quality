<?php

declare(strict_types=1);

use App\Enums\InterventionStatus;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Services\InterventionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;

/**
 * Block A / #54 — `interventions:sweep-missed` Artisan command flips
 * planned interventions whose scheduled end time + grace window has
 * passed without check-in to status='missed'. Scheduled every 15 min
 * via routes/console.php.
 *
 * Time-travel via Carbon::setTestNow.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('marks an intervention whose end time + grace passed as missed', function (): void {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();

    // End time 2h ago — well past a 30-minute grace window.
    $start = now()->subHours(3);
    $end = now()->subHours(2);

    $intervention = Intervention::factory()
        ->forStructure($coord->structure)
        ->state([
            'beneficiary_id' => $beneficiary->id,
            'intervenant_id' => $coord->id,
            'planned_date' => $start->format('Y-m-d'),
            'planned_start_time' => $start->format('H:i:s'),
            'planned_end_time' => $end->format('H:i:s'),
            'status' => InterventionStatus::Planned->value,
        ])
        ->create();

    expect(app(InterventionService::class)->sweepMissed(graceMinutes: 30))->toBe(1);
    expect($intervention->fresh()->status)->toBe(InterventionStatus::Missed);
});

it('does NOT touch interventions still inside the grace window', function (): void {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();

    // planned_end_time was 10 minutes ago; grace is 30 minutes — should NOT sweep.
    $intervention = Intervention::factory()
        ->forStructure($coord->structure)
        ->state([
            'beneficiary_id' => $beneficiary->id,
            'intervenant_id' => $coord->id,
            'planned_date' => now()->format('Y-m-d'),
            'planned_start_time' => now()->subMinutes(70)->format('H:i:s'),
            'planned_end_time' => now()->subMinutes(10)->format('H:i:s'),
            'status' => InterventionStatus::Planned->value,
        ])
        ->create();

    expect(app(InterventionService::class)->sweepMissed(graceMinutes: 30))->toBe(0);
    expect($intervention->fresh()->status)->toBe(InterventionStatus::Planned);
});

it('does NOT touch already-completed interventions', function (): void {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();

    $intervention = Intervention::factory()
        ->forStructure($coord->structure)
        ->state([
            'beneficiary_id' => $beneficiary->id,
            'intervenant_id' => $coord->id,
            'planned_date' => now()->subDay()->format('Y-m-d'),
            'planned_start_time' => '08:00:00',
            'planned_end_time' => '09:00:00',
            'status' => InterventionStatus::Completed->value,
            'actual_start_at' => now()->subDay(),
            'actual_end_at' => now()->subDay()->addHour(),
        ])
        ->create();

    app(InterventionService::class)->sweepMissed();
    expect($intervention->fresh()->status)->toBe(InterventionStatus::Completed);
});

it('sweeps across multiple tenants in one pass (console-level)', function (): void {
    // Both tenants have a stale intervention from yesterday. Sweep must
    // hit BOTH — bypasses the BelongsToStructure global scope.
    $yesterday = now()->subDay();

    $a = actingAsRole('coordinateur');
    $aBen = Beneficiary::factory()->forStructure($a->structure)->create();
    Intervention::factory()->forStructure($a->structure)->state([
        'beneficiary_id' => $aBen->id,
        'intervenant_id' => $a->id,
        'planned_date' => $yesterday->format('Y-m-d'),
        'planned_start_time' => '06:00:00',
        'planned_end_time' => '07:00:00',
        'status' => InterventionStatus::Planned->value,
    ])->create();

    $b = actingAsRole('coordinateur');
    $bBen = Beneficiary::factory()->forStructure($b->structure)->create();
    Intervention::factory()->forStructure($b->structure)->state([
        'beneficiary_id' => $bBen->id,
        'intervenant_id' => $b->id,
        'planned_date' => $yesterday->format('Y-m-d'),
        'planned_start_time' => '06:00:00',
        'planned_end_time' => '07:00:00',
        'status' => InterventionStatus::Planned->value,
    ])->create();

    expect(app(InterventionService::class)->sweepMissed())->toBe(2);
});

it('the artisan command runs and reports the count', function (): void {
    $this->artisan('interventions:sweep-missed', ['--grace' => 5])
        ->expectsOutputToContain('Swept 0 stale interventions as missed')
        ->assertSuccessful();
});

it('is registered in the schedule', function (): void {
    $events = collect(app(Schedule::class)->events());
    $hit = $events->first(fn ($e) => str_contains($e->command ?? '', 'interventions:sweep-missed'));

    expect($hit)->not->toBeNull('interventions:sweep-missed must be in routes/console.php');
});
