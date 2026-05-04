<?php

declare(strict_types=1);

use App\Models\Certification;
use App\Models\Habilitation;
use App\Models\Structure;
use App\Models\TrainingAttendance;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

// ── HabilitationPolicy / CertificationPolicy ─────────────────────────────

it('user can view their own habilitation', function (): void {
    $intervenant = actingAsRole('intervenant');
    $hab = Habilitation::factory()->forUser($intervenant)->create();

    expect($intervenant->can('view', $hab))->toBeTrue();
});

it('user cannot view another user habilitation without team perm', function (): void {
    $intervenant = actingAsRole('intervenant');
    $other = actingAsRole('intervenant', $intervenant->structure);
    $hab = Habilitation::factory()->forUser($other)->create();

    expect($intervenant->can('view', $hab))->toBeFalse();
});

it('coordinateur can view team habilitations', function (): void {
    $coord = actingAsRole('coordinateur');
    $intervenant = actingAsRole('intervenant', $coord->structure);
    $hab = Habilitation::factory()->forUser($intervenant)->create();

    expect($coord->can('view', $hab))->toBeTrue();
});

it('only certifications.record can record a certification', function (): void {
    $intervenant = actingAsRole('intervenant');
    $rh = actingAsRole('rh', $intervenant->structure);

    expect($intervenant->can('create', Certification::class))->toBeFalse();
    expect($rh->can('create', Certification::class))->toBeTrue();
});

it('cross-tenant guard denies habilitation access', function (): void {
    $userA = actingAsRole('rh');
    $structureB = Structure::factory()->create();
    $foreignHab = Habilitation::factory()->forStructure($structureB)->create();

    expect($userA->can('view', $foreignHab))->toBeFalse();
});

// ── TrainingPlanPolicy ───────────────────────────────────────────────────

it('any user can view training plans', function (): void {
    $intervenant = actingAsRole('intervenant');
    $plan = TrainingPlan::factory()->forStructure($intervenant->structure)->create();

    expect($intervenant->can('view', $plan))->toBeTrue();
});

it('only trainings.plan can draft a plan', function (): void {
    $intervenant = actingAsRole('intervenant');
    $rh = actingAsRole('rh', $intervenant->structure);

    expect($intervenant->can('create', TrainingPlan::class))->toBeFalse();
    expect($rh->can('create', TrainingPlan::class))->toBeTrue();
});

// ── TrainingSessionPolicy ────────────────────────────────────────────────

it('only trainings.plan can create a session', function (): void {
    $intervenant = actingAsRole('intervenant');
    $rh = actingAsRole('rh', $intervenant->structure);

    expect($intervenant->can('create', TrainingSession::class))->toBeFalse();
    expect($rh->can('create', TrainingSession::class))->toBeTrue();
});

// ── TrainingAttendancePolicy ─────────────────────────────────────────────

it('any user can register themselves for a session (create)', function (): void {
    $intervenant = actingAsRole('intervenant');

    expect($intervenant->can('create', TrainingAttendance::class))->toBeTrue();
});

it('user can view their own attendance', function (): void {
    $intervenant = actingAsRole('intervenant');
    $plan = TrainingPlan::factory()->forStructure($intervenant->structure)->create();
    $session = TrainingSession::factory()->forPlan($plan)->create();
    $attendance = TrainingAttendance::factory()->forSession($session, $intervenant)->create();

    expect($intervenant->can('view', $attendance))->toBeTrue();
});

it('user cannot view another user attendance without trainings.record', function (): void {
    $intervenant = actingAsRole('intervenant');
    $other = actingAsRole('intervenant', $intervenant->structure);
    $plan = TrainingPlan::factory()->forStructure($intervenant->structure)->create();
    $session = TrainingSession::factory()->forPlan($plan)->create();
    $attendance = TrainingAttendance::factory()->forSession($session, $other)->create();

    expect($intervenant->can('view', $attendance))->toBeFalse();
});

it('only trainings.record can mark attended (update)', function (): void {
    $intervenant = actingAsRole('intervenant');
    $coord = actingAsRole('coordinateur', $intervenant->structure);
    $plan = TrainingPlan::factory()->forStructure($intervenant->structure)->create();
    $session = TrainingSession::factory()->forPlan($plan)->create();
    $attendance = TrainingAttendance::factory()->forSession($session, $intervenant)->create();

    expect($intervenant->can('update', $attendance))->toBeFalse();
    expect($coord->can('update', $attendance))->toBeTrue();
});
