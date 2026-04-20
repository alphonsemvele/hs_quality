<?php

use App\Enums\CarePlanStatus;
use App\Enums\TaskFrequency;
use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\PlannedTask;
use App\Models\Structure;
use App\Services\PlannedTaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(PlannedTaskService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $beneficiary = Beneficiary::factory()->forStructure($this->structure)->create();
    $this->plan = CarePlan::factory()->forBeneficiary($beneficiary)->create([
        'status' => CarePlanStatus::Draft->value,
    ]);
});

it('creates a task and auto-assigns task_order = max+1', function () {
    PlannedTask::factory()->forCarePlan($this->plan)->order(0)->create();
    PlannedTask::factory()->forCarePlan($this->plan)->order(1)->create();

    $task = $this->service->create($this->plan, [
        'title' => 'Préparation déjeuner',
        'frequency' => TaskFrequency::Daily->value,
    ]);

    expect($task)->toBeInstanceOf(PlannedTask::class)
        ->and($task->task_order)->toBe(2)
        ->and($task->structure_id)->toBe($this->structure->id);
});

it('respects an explicit task_order on create', function () {
    PlannedTask::factory()->forCarePlan($this->plan)->order(5)->create();

    $task = $this->service->create($this->plan, [
        'title' => 'Insertion intermédiaire',
        'frequency' => TaskFrequency::Daily->value,
        'task_order' => 2,
    ]);

    expect($task->task_order)->toBe(2);
});

it('updates a task in a draft plan', function () {
    $task = PlannedTask::factory()->forCarePlan($this->plan)->create([
        'title' => 'Old title',
        'duration_minutes' => 15,
    ]);

    $updated = $this->service->update($task, [
        'title' => 'New title',
        'duration_minutes' => 30,
    ]);

    expect($updated->title)->toBe('New title')
        ->and($updated->duration_minutes)->toBe(30);
});

it('refuses mass-assignment of structure_id and care_plan_id on update', function () {
    $task = PlannedTask::factory()->forCarePlan($this->plan)->create();
    $foreignStructure = Structure::factory()->create();
    $foreignBeneficiary = Beneficiary::factory()->forStructure($foreignStructure)->create();
    $foreignPlan = CarePlan::factory()->forBeneficiary($foreignBeneficiary)->create();

    $this->service->update($task, [
        'title' => 'Renamed',
        'structure_id' => $foreignStructure->id,
        'care_plan_id' => $foreignPlan->id,
    ]);

    $fresh = $task->fresh();
    expect($fresh->title)->toBe('Renamed')
        ->and($fresh->structure_id)->toBe($this->structure->id)
        ->and($fresh->care_plan_id)->toBe($this->plan->id);
});

it('soft-deletes a task', function () {
    $task = PlannedTask::factory()->forCarePlan($this->plan)->create();

    $this->service->delete($task);

    expect(PlannedTask::count())->toBe(0)
        ->and(PlannedTask::withTrashed()->count())->toBe(1);
});

it('refuses to create a task on an archived plan', function () {
    $this->plan->update(['status' => CarePlanStatus::Archived->value]);

    expect(fn () => $this->service->create($this->plan->fresh(), [
        'title' => 'Should fail',
        'frequency' => TaskFrequency::Daily->value,
    ]))->toThrow(HttpException::class);

    expect(PlannedTask::count())->toBe(0);
});

it('refuses to update a task in an archived plan', function () {
    $task = PlannedTask::factory()->forCarePlan($this->plan)->create(['title' => 'Original']);
    $this->plan->update(['status' => CarePlanStatus::Archived->value]);
    $task = $task->fresh();

    expect(fn () => $this->service->update($task, ['title' => 'Mutated']))
        ->toThrow(HttpException::class);

    expect($task->fresh()->title)->toBe('Original');
});

it('refuses to delete a task in an archived plan', function () {
    $task = PlannedTask::factory()->forCarePlan($this->plan)->create();
    $this->plan->update(['status' => CarePlanStatus::Archived->value]);
    $task = $task->fresh();

    expect(fn () => $this->service->delete($task))
        ->toThrow(HttpException::class);

    expect(PlannedTask::count())->toBe(1);
});
