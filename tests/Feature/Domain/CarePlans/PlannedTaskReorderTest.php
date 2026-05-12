<?php

use App\Enums\CarePlanStatus;
use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\PlannedTask;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makePlanForCoord(User $coord, CarePlanStatus $status = CarePlanStatus::Draft): CarePlan
{
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();

    return CarePlan::factory()->forStructure($coord->structure)->create([
        'beneficiary_id' => $beneficiary->id,
        'status' => $status->value,
    ]);
}

it('reorders tasks according to the posted order', function () {
    $coord = actingAsRole('coordinateur');
    $plan = makePlanForCoord($coord);

    $taskA = PlannedTask::factory()->forCarePlan($plan)->order(0)->create();
    $taskB = PlannedTask::factory()->forCarePlan($plan)->order(1)->create();
    $taskC = PlannedTask::factory()->forCarePlan($plan)->order(2)->create();

    // New order: C, A, B
    $response = $this->post("/care-plans/{$plan->id}/tasks/reorder", [
        'order' => [$taskC->id, $taskA->id, $taskB->id],
    ]);

    $response->assertRedirect();
    expect($taskC->fresh()->task_order)->toBe(0);
    expect($taskA->fresh()->task_order)->toBe(1);
    expect($taskB->fresh()->task_order)->toBe(2);
});

it('refuses reorder containing ids from another plan', function () {
    $coord = actingAsRole('coordinateur');
    $planA = makePlanForCoord($coord);
    $planB = makePlanForCoord($coord);

    $taskA = PlannedTask::factory()->forCarePlan($planA)->order(0)->create();
    $taskB = PlannedTask::factory()->forCarePlan($planB)->order(0)->create();

    // Service throws HttpException(422) which Laravel renders as 422 on JSON
    // and as a redirect with a "general" exception page on web. Asserting via
    // JSON for a clean error signal.
    $this->postJson("/care-plans/{$planA->id}/tasks/reorder", [
        'order' => [$taskB->id, $taskA->id],
    ])->assertStatus(422);

    // Order on the legitimate task A should remain unchanged.
    expect($taskA->fresh()->task_order)->toBe(0);
});

it('refuses reorder on an archived plan via the policy layer', function () {
    $coord = actingAsRole('coordinateur');
    $plan = makePlanForCoord($coord, CarePlanStatus::Archived);
    $task = PlannedTask::factory()->forCarePlan($plan)->order(0)->create();

    // The policy rejects update on archived plans (403) before the service's
    // own guard (which would be 409). Either is correct — we lock in 403 since
    // the controller's authorize() runs first.
    $this->post("/care-plans/{$plan->id}/tasks/reorder", [
        'order' => [$task->id],
    ])->assertStatus(403);
});

it('validates that order is a non-empty array of uuids', function () {
    $coord = actingAsRole('coordinateur');
    $plan = makePlanForCoord($coord);

    $this->post("/care-plans/{$plan->id}/tasks/reorder", [
        'order' => [],
    ])->assertSessionHasErrors('order');

    $this->post("/care-plans/{$plan->id}/tasks/reorder", [
        'order' => ['not-a-uuid'],
    ])->assertSessionHasErrors('order.0');
});

it('rejects duplicate ids in the order payload', function () {
    $coord = actingAsRole('coordinateur');
    $plan = makePlanForCoord($coord);
    $task = PlannedTask::factory()->forCarePlan($plan)->order(0)->create();

    $this->post("/care-plans/{$plan->id}/tasks/reorder", [
        'order' => [$task->id, $task->id],
    ])->assertSessionHasErrors('order.0');
});

it('rejects unauthenticated reorder', function () {
    $this->post('/care-plans/019dfc2d-1a71-70a4-bfa9-797165f21f05/tasks/reorder', ['order' => ['019dfc2d-1a71-70a4-bfa9-797165f21f06']])
        ->assertRedirect('/login');
});
