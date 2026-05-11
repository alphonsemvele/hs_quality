<?php

declare(strict_types=1);

use App\Models\Certification;
use App\Models\Habilitation;
use App\Models\Structure;
use App\Models\TrainingAttendance;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\User;

/**
 * Cross-tenant leak tests for the M5.1 habilitations and M5.2
 * certifications tables. Spec: PHASE2_PROGRESS.md M5.7 partial.
 */
beforeEach(function (): void {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

it('only returns habilitations from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $userA = User::factory()->forStructure($a)->create();
    $userB = User::factory()->forStructure($b)->create();

    Habilitation::factory()->forUser($userA)->count(2)->create();
    Habilitation::factory()->forUser($userB)->count(3)->create();

    app()->instance('current_structure', $a);
    expect(Habilitation::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(Habilitation::count())->toBe(3);
});

it('only returns certifications from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $userA = User::factory()->forStructure($a)->create();
    $userB = User::factory()->forStructure($b)->create();

    Certification::factory()->forUser($userA)->count(1)->create();
    Certification::factory()->forUser($userB)->count(4)->create();

    app()->instance('current_structure', $a);
    expect(Certification::count())->toBe(1);

    app()->instance('current_structure', $b);
    expect(Certification::count())->toBe(4);
});

it('only returns training_plans from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    TrainingPlan::factory()->forStructure($a)->count(2)->create();
    TrainingPlan::factory()->forStructure($b)->count(3)->create();

    app()->instance('current_structure', $a);
    expect(TrainingPlan::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(TrainingPlan::count())->toBe(3);
});

it('only returns training_sessions from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $planA = TrainingPlan::factory()->forStructure($a)->create();
    $planB = TrainingPlan::factory()->forStructure($b)->create();

    TrainingSession::factory()->forPlan($planA)->count(2)->create();
    TrainingSession::factory()->forPlan($planB)->count(4)->create();

    app()->instance('current_structure', $a);
    expect(TrainingSession::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(TrainingSession::count())->toBe(4);
});

it('only returns training_attendances from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $planA = TrainingPlan::factory()->forStructure($a)->create();
    $sessionA = TrainingSession::factory()->forPlan($planA)->create();

    $planB = TrainingPlan::factory()->forStructure($b)->create();
    $sessionB = TrainingSession::factory()->forPlan($planB)->create();

    TrainingAttendance::factory()->forSession($sessionA)->count(2)->create();
    TrainingAttendance::factory()->forSession($sessionB)->count(5)->create();

    app()->instance('current_structure', $a);
    expect(TrainingAttendance::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(TrainingAttendance::count())->toBe(5);
});
