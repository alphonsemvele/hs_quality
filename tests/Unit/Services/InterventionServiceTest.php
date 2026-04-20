<?php

use App\Enums\InterventionStatus;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\Structure;
use App\Models\User;
use App\Services\InterventionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(InterventionService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->intervenant = User::factory()->create([
        'structure_id' => $this->structure->id,
        'type' => 'intervenant',
    ]);
    $this->beneficiary = Beneficiary::factory()->forStructure($this->structure)->create();
});

it('creates an intervention with status planned', function () {
    $intervention = $this->service->create([
        'intervenant_id' => $this->intervenant->id,
        'beneficiary_id' => $this->beneficiary->id,
        'planned_date' => '2026-05-01',
    ], $this->intervenant);

    expect($intervention)->toBeInstanceOf(Intervention::class)
        ->and($intervention->status)->toBe(InterventionStatus::Planned)
        ->and($intervention->structure_id)->toBe($this->structure->id);
});

it('updates editable fields on a planned intervention', function () {
    $intervention = Intervention::factory()
        ->forBeneficiary($this->beneficiary)
        ->forIntervenant($this->intervenant)
        ->planned()
        ->create();

    $updated = $this->service->update($intervention, [
        'planned_date' => '2026-05-10',
        'planned_start_time' => '09:00',
    ]);

    expect($updated->planned_date->format('Y-m-d'))->toBe('2026-05-10')
        ->and($updated->planned_start_time)->toStartWith('09:00');
});

it('refuses to update a terminal intervention', function () {
    $intervention = Intervention::factory()
        ->forBeneficiary($this->beneficiary)
        ->forIntervenant($this->intervenant)
        ->completed()
        ->create();

    expect(fn () => $this->service->update($intervention, ['planned_date' => '2026-06-01']))
        ->toThrow(HttpException::class);
});

it('update strips structure_id and beneficiary_id to prevent reparenting', function () {
    $other = Structure::factory()->create();
    $intervention = Intervention::factory()
        ->forBeneficiary($this->beneficiary)
        ->forIntervenant($this->intervenant)
        ->planned()
        ->create();

    $this->service->update($intervention, [
        'structure_id' => $other->id,
        'beneficiary_id' => Beneficiary::factory()->forStructure($other)->create()->id,
        'planned_date' => '2026-05-15',
    ]);

    $fresh = $intervention->fresh();
    expect($fresh->structure_id)->toBe($this->structure->id)
        ->and($fresh->beneficiary_id)->toBe($this->beneficiary->id);
});

it('checkin transitions planned → in_progress and records actual_start_at', function () {
    $intervention = Intervention::factory()
        ->forBeneficiary($this->beneficiary)
        ->forIntervenant($this->intervenant)
        ->planned()
        ->create();

    $updated = $this->service->checkIn($intervention);

    expect($updated->status)->toBe(InterventionStatus::InProgress)
        ->and($updated->actual_start_at)->not->toBeNull();
});

it('checkin stores GPS coordinates', function () {
    $intervention = Intervention::factory()
        ->forBeneficiary($this->beneficiary)
        ->forIntervenant($this->intervenant)
        ->planned()
        ->create();

    $updated = $this->service->checkIn($intervention, [
        'latitude' => '3.86667',
        'longitude' => '11.51667',
    ]);

    expect((float) $updated->checkin_latitude)->toBeGreaterThan(3.0)
        ->and((float) $updated->checkin_longitude)->toBeGreaterThan(11.0);
});

it('refuses checkin when intervention is not planned', function () {
    $intervention = Intervention::factory()
        ->forBeneficiary($this->beneficiary)
        ->forIntervenant($this->intervenant)
        ->inProgress()
        ->create();

    expect(fn () => $this->service->checkIn($intervention))
        ->toThrow(HttpException::class);
});

it('checkout transitions in_progress → completed and records actual_end_at', function () {
    $intervention = Intervention::factory()
        ->forBeneficiary($this->beneficiary)
        ->forIntervenant($this->intervenant)
        ->inProgress()
        ->create();

    $updated = $this->service->checkOut($intervention, ['report_text' => 'Visite effectuée sans incident.']);

    expect($updated->status)->toBe(InterventionStatus::Completed)
        ->and($updated->actual_end_at)->not->toBeNull();
});

it('refuses checkout when intervention is not in progress', function () {
    $intervention = Intervention::factory()
        ->forBeneficiary($this->beneficiary)
        ->forIntervenant($this->intervenant)
        ->planned()
        ->create();

    expect(fn () => $this->service->checkOut($intervention))
        ->toThrow(HttpException::class);
});

it('cancel sets status to cancelled with a reason', function () {
    $intervention = Intervention::factory()
        ->forBeneficiary($this->beneficiary)
        ->forIntervenant($this->intervenant)
        ->planned()
        ->create();

    $updated = $this->service->cancel($intervention, 'Bénéficiaire absent');

    expect($updated->status)->toBe(InterventionStatus::Cancelled)
        ->and($updated->cancellation_reason)->toBe('Bénéficiaire absent');
});

it('refuses to cancel a terminal intervention', function () {
    $intervention = Intervention::factory()
        ->forBeneficiary($this->beneficiary)
        ->forIntervenant($this->intervenant)
        ->completed()
        ->create();

    expect(fn () => $this->service->cancel($intervention, 'Too late'))
        ->toThrow(HttpException::class);
});

it('marks a planned intervention as missed', function () {
    $intervention = Intervention::factory()
        ->forBeneficiary($this->beneficiary)
        ->forIntervenant($this->intervenant)
        ->planned()
        ->create(['planned_date' => now()->subDay()]);

    $updated = $this->service->markMissed($intervention);

    expect($updated->status)->toBe(InterventionStatus::Missed);
});

it('soft-deletes an intervention', function () {
    $intervention = Intervention::factory()
        ->forBeneficiary($this->beneficiary)
        ->forIntervenant($this->intervenant)
        ->create();

    $this->service->delete($intervention);

    expect(Intervention::count())->toBe(0)
        ->and(Intervention::withTrashed()->count())->toBe(1);
});
