<?php

declare(strict_types=1);

use App\Enums\TrainingAttendanceStatus;
use App\Models\TrainingAttendance;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('registers an intervenant on a session via the web', function (): void {
    $admin = actingAsRole('dirigeant');
    $plan = TrainingPlan::factory()->forStructure($admin->structure)->create();
    $session = TrainingSession::factory()->create([
        'structure_id' => $admin->structure_id,
        'training_plan_id' => $plan->id,
    ]);
    $target = User::factory()->forStructure($admin->structure)->create();

    $this->post("/formations/sessions/{$session->id}/attendances", [
        'user_id' => $target->id,
    ])->assertRedirect();

    expect(TrainingAttendance::query()->where('training_session_id', $session->id)->where('user_id', $target->id)->count())->toBe(1);
});

it('marks an attendance as attended', function (): void {
    $admin = actingAsRole('dirigeant');
    $plan = TrainingPlan::factory()->forStructure($admin->structure)->create();
    $session = TrainingSession::factory()->create([
        'structure_id' => $admin->structure_id,
        'training_plan_id' => $plan->id,
    ]);
    $target = User::factory()->forStructure($admin->structure)->create();
    $att = TrainingAttendance::factory()->create([
        'structure_id' => $admin->structure_id,
        'training_session_id' => $session->id,
        'user_id' => $target->id,
        'status' => TrainingAttendanceStatus::Registered,
    ]);

    $this->post("/formations/attendances/{$att->id}/mark-attended")->assertRedirect();

    expect($att->fresh()->status)->toBe(TrainingAttendanceStatus::Attended);
});

it('lets a manager cancel an attendance', function (): void {
    $admin = actingAsRole('dirigeant');
    $plan = TrainingPlan::factory()->forStructure($admin->structure)->create();
    $session = TrainingSession::factory()->create([
        'structure_id' => $admin->structure_id,
        'training_plan_id' => $plan->id,
    ]);
    $target = User::factory()->forStructure($admin->structure)->create();
    $att = TrainingAttendance::factory()->create([
        'structure_id' => $admin->structure_id,
        'training_session_id' => $session->id,
        'user_id' => $target->id,
        'status' => TrainingAttendanceStatus::Registered,
    ]);

    $this->post("/formations/attendances/{$att->id}/cancel")->assertRedirect();

    expect($att->fresh()->status)->toBe(TrainingAttendanceStatus::Cancelled);
});

it('lets an intervenant self-cancel their own attendance', function (): void {
    $intervenant = actingAsRole('intervenant');
    $plan = TrainingPlan::factory()->forStructure($intervenant->structure)->create();
    $session = TrainingSession::factory()->create([
        'structure_id' => $intervenant->structure_id,
        'training_plan_id' => $plan->id,
    ]);
    $att = TrainingAttendance::factory()->create([
        'structure_id' => $intervenant->structure_id,
        'training_session_id' => $session->id,
        'user_id' => $intervenant->id,
        'status' => TrainingAttendanceStatus::Registered,
    ]);

    $this->post("/formations/attendances/{$att->id}/cancel")->assertRedirect();

    expect($att->fresh()->status)->toBe(TrainingAttendanceStatus::Cancelled);
});

it('forbids an intervenant from cancelling someone else\'s attendance', function (): void {
    $intervenant = actingAsRole('intervenant');
    $plan = TrainingPlan::factory()->forStructure($intervenant->structure)->create();
    $session = TrainingSession::factory()->create([
        'structure_id' => $intervenant->structure_id,
        'training_plan_id' => $plan->id,
    ]);
    $other = User::factory()->forStructure($intervenant->structure)->create();
    $att = TrainingAttendance::factory()->create([
        'structure_id' => $intervenant->structure_id,
        'training_session_id' => $session->id,
        'user_id' => $other->id,
        'status' => TrainingAttendanceStatus::Registered,
    ]);

    $this->post("/formations/attendances/{$att->id}/cancel")->assertForbidden();
});
