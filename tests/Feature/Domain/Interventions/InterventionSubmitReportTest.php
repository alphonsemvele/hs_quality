<?php

declare(strict_types=1);

use App\Enums\InterventionStatus;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Direct (web) coverage for POST /interventions/{intervention}/report —
 * the post-checkout report-amendment endpoint that closes Phase 1's
 * "submit report" line item from M2 W1-2 of IMPLEMENTATION_PLAN.txt.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('coordinator can submit a report on a completed intervention', function (): void {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->forStructure($coord->structure)->intervenant()->create();

    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->state([
            'status' => InterventionStatus::Completed->value,
            'actual_start_at' => now()->subHour(),
            'actual_end_at' => now(),
        ])
        ->create();

    $this->post("/interventions/{$intervention->id}/report", [
        'report_text' => 'Bénéficiaire calme, soins effectués sans difficulté.',
    ])->assertRedirect();

    expect($intervention->fresh()->report_text)
        ->toBe('Bénéficiaire calme, soins effectués sans difficulté.');
});

it('intervenant can submit a report on their own in-progress intervention', function (): void {
    $intervenant = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($intervenant->structure)->create();

    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->state([
            'status' => InterventionStatus::InProgress->value,
            'actual_start_at' => now()->subMinutes(20),
        ])
        ->create();

    $this->post("/interventions/{$intervention->id}/report", [
        'report_text' => 'Tour démarrée.',
    ])->assertRedirect();

    expect($intervention->fresh()->report_text)->toBe('Tour démarrée.');
});

it('rejects report submission on a cancelled intervention', function (): void {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->forStructure($coord->structure)->intervenant()->create();

    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->state(['status' => InterventionStatus::Cancelled->value])
        ->create();

    $this->post("/interventions/{$intervention->id}/report", [
        'report_text' => 'Anything.',
    ])->assertForbidden();

    expect($intervention->fresh()->report_text)->toBeNull();
});

it('rejects an empty report_text at validation', function (): void {
    $intervenant = actingAsRole('intervenant');
    $beneficiary = Beneficiary::factory()->forStructure($intervenant->structure)->create();

    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->state(['status' => InterventionStatus::InProgress->value])
        ->create();

    $this->post("/interventions/{$intervention->id}/report", [
        'report_text' => '',
    ])->assertSessionHasErrors('report_text');
});

it('merges with conflict markers when a second write differs', function (): void {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervenant = User::factory()->forStructure($coord->structure)->intervenant()->create();

    $intervention = Intervention::factory()
        ->forBeneficiary($beneficiary)
        ->forIntervenant($intervenant)
        ->state([
            'status' => InterventionStatus::Completed->value,
            'actual_start_at' => now()->subHour(),
            'actual_end_at' => now(),
            'report_text' => 'First narrative.',
        ])
        ->create();

    $this->post("/interventions/{$intervention->id}/report", [
        'report_text' => 'Second narrative — different details.',
    ])->assertRedirect();

    expect($intervention->fresh()->report_text)->toBe(
        "<<<<<<< saved\nFirst narrative.\n=======\nSecond narrative — different details.\n>>>>>>> incoming"
    );
});
