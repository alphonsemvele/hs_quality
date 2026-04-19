<?php

use App\Enums\CarePlanStatus;
use App\Enums\TaskFrequency;
use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\PlannedTask;
use App\Models\Structure;
use App\Services\CarePlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(CarePlanService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->beneficiary = Beneficiary::factory()->forStructure($this->structure)->create();
});

it('creates a care plan for a beneficiary', function () {
    $plan = $this->service->create([
        'beneficiary_id' => $this->beneficiary->id,
        'title' => 'Plan initial',
        'objectives' => 'Maintenir l\'autonomie à domicile',
        'start_date' => '2026-04-01',
    ]);

    expect($plan)->toBeInstanceOf(CarePlan::class)
        ->and($plan->title)->toBe('Plan initial')
        ->and($plan->beneficiary_id)->toBe($this->beneficiary->id)
        ->and($plan->structure_id)->toBe($this->structure->id)
        ->and($plan->status)->toBe(CarePlanStatus::Draft);
});

it('activates a draft plan', function () {
    $plan = CarePlan::factory()->forBeneficiary($this->beneficiary)->create([
        'status' => CarePlanStatus::Draft->value,
    ]);

    $activated = $this->service->activate($plan);

    expect($activated->status)->toBe(CarePlanStatus::Active);
});

it('auto-archives any existing active plan when activating another', function () {
    $oldPlan = CarePlan::factory()->forBeneficiary($this->beneficiary)->active()->create();
    $newPlan = CarePlan::factory()->forBeneficiary($this->beneficiary)->create([
        'status' => CarePlanStatus::Draft->value,
        'title' => 'Nouveau plan',
    ]);

    $this->service->activate($newPlan);

    expect($oldPlan->fresh()->status)->toBe(CarePlanStatus::Archived)
        ->and($oldPlan->fresh()->archived_reason)->toContain('Nouveau plan')
        ->and($newPlan->fresh()->status)->toBe(CarePlanStatus::Active);
});

it('archives a plan with the supplied reason', function () {
    $plan = CarePlan::factory()->forBeneficiary($this->beneficiary)->active()->create();

    $archived = $this->service->archive($plan, 'Bénéficiaire sorti du service');

    expect($archived->status)->toBe(CarePlanStatus::Archived)
        ->and($archived->archived_reason)->toBe('Bénéficiaire sorti du service')
        ->and($archived->archived_at)->not->toBeNull();
});

it('copies a plan from a template to another beneficiary with all tasks', function () {
    $sourceBeneficiary = Beneficiary::factory()->forStructure($this->structure)->create();
    $sourcePlan = CarePlan::factory()->forBeneficiary($sourceBeneficiary)->create();

    PlannedTask::factory()->forCarePlan($sourcePlan)->order(1)->create(['title' => 'Toilette du matin']);
    PlannedTask::factory()->forCarePlan($sourcePlan)->order(2)->weekly()->create(['title' => 'Courses']);
    PlannedTask::factory()->forCarePlan($sourcePlan)->order(3)->optional()->create(['title' => 'Promenade']);

    $targetBeneficiary = Beneficiary::factory()->forStructure($this->structure)->create();

    $copied = $this->service->copyFromTemplate(
        source: $sourcePlan,
        target: $targetBeneficiary,
        title: 'Plan inspiré du modèle',
    );

    expect($copied->beneficiary_id)->toBe($targetBeneficiary->id)
        ->and($copied->status)->toBe(CarePlanStatus::Draft)
        ->and($copied->structure_id)->toBe($this->structure->id)
        ->and($copied->tasks)->toHaveCount(3);

    $taskTitles = $copied->tasks->pluck('title')->toArray();
    expect($taskTitles)->toContain('Toilette du matin', 'Courses', 'Promenade');

    $weeklyTask = $copied->tasks->firstWhere('title', 'Courses');
    expect($weeklyTask->frequency)->toBe(TaskFrequency::Weekly)
        ->and($weeklyTask->frequency_details)->toBe(['days' => ['monday', 'wednesday', 'friday']]);
});

it('refuses to copy a plan across structures', function () {
    $foreignStructure = Structure::factory()->create();
    $foreignBeneficiary = Beneficiary::factory()->forStructure($foreignStructure)->create();
    $foreignPlan = CarePlan::factory()->forBeneficiary($foreignBeneficiary)->create();

    expect(fn () => $this->service->copyFromTemplate(
        source: $foreignPlan,
        target: $this->beneficiary,
        title: 'Should fail',
    ))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('encrypts the objectives field at rest', function () {
    $plan = $this->service->create([
        'beneficiary_id' => $this->beneficiary->id,
        'title' => 'Plan',
        'objectives' => 'Prévention chutes - risque d\'ostéoporose connu',
        'start_date' => '2026-04-01',
    ]);

    expect($plan->objectives)->toBe('Prévention chutes - risque d\'ostéoporose connu');

    $raw = DB::table('care_plans')->where('id', $plan->id)->first();
    expect($raw->objectives)->not->toBe('Prévention chutes - risque d\'ostéoporose connu');
});
