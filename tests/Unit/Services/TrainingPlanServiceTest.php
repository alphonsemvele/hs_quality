<?php

declare(strict_types=1);

use App\Enums\TrainingAttendanceStatus;
use App\Enums\TrainingPlanStatus;
use App\Models\Structure;
use App\Models\TrainingAttendance;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\TrainingPlanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(TrainingPlanService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->author = User::factory()->forStructure($this->structure)->create();
});

// ── Plan lifecycle ───────────────────────────────────────────────────────

it('drafts a plan with author + draft status', function (): void {
    $plan = $this->service->draft($this->structure, $this->author, 2026, 'Bientraitance');

    expect($plan->status)->toBe(TrainingPlanStatus::Draft)
        ->and($plan->created_by)->toBe($this->author->id)
        ->and($plan->year)->toBe(2026);
});

it('publishes a draft plan', function (): void {
    $plan = TrainingPlan::factory()->forStructure($this->structure)->create();

    $published = $this->service->publish($plan);

    expect($published->status)->toBe(TrainingPlanStatus::Published)
        ->and($published->published_at)->not->toBeNull();
});

it('publishing an already-published plan is a no-op', function (): void {
    $plan = TrainingPlan::factory()->forStructure($this->structure)->published()->create();
    $alreadyPublishedAt = $plan->published_at;

    $republished = $this->service->publish($plan);

    expect($republished->status)->toBe(TrainingPlanStatus::Published);
    // published_at should not have moved
    expect($republished->published_at?->toIso8601String())->toBe($alreadyPublishedAt?->toIso8601String());
});

it('cannot publish an archived plan', function (): void {
    $plan = TrainingPlan::factory()->forStructure($this->structure)->archived()->create();

    expect(fn () => $this->service->publish($plan))->toThrow(HttpException::class);
});

// ── Sessions ─────────────────────────────────────────────────────────────

it('adds a session to a published plan', function (): void {
    $plan = TrainingPlan::factory()->forStructure($this->structure)->published()->create();

    $session = $this->service->addSession(
        $plan,
        title: 'BLS — module 1',
        startsAt: Carbon::parse('2026-06-15 09:00'),
        endsAt: Carbon::parse('2026-06-15 12:00'),
        capacity: 12,
    );

    expect($session->training_plan_id)->toBe($plan->id)
        ->and($session->structure_id)->toBe($this->structure->id)
        ->and($session->capacity)->toBe(12);
});

it('refuses to add a session to an archived plan', function (): void {
    $plan = TrainingPlan::factory()->forStructure($this->structure)->archived()->create();

    expect(fn () => $this->service->addSession(
        $plan, 'X',
        Carbon::parse('2026-06-15 09:00'),
        Carbon::parse('2026-06-15 12:00'),
    ))->toThrow(HttpException::class);
});

it('refuses session whose end is before its start', function (): void {
    $plan = TrainingPlan::factory()->forStructure($this->structure)->create();

    expect(fn () => $this->service->addSession(
        $plan, 'X',
        Carbon::parse('2026-06-15 12:00'),
        Carbon::parse('2026-06-15 09:00'),
    ))->toThrow(HttpException::class);
});

// ── Attendances ──────────────────────────────────────────────────────────

it('registers a user once per session', function (): void {
    $plan = TrainingPlan::factory()->forStructure($this->structure)->create();
    $session = TrainingSession::factory()->forPlan($plan)->create(['capacity' => 5]);
    $user = User::factory()->forStructure($this->structure)->create();

    $a1 = $this->service->register($session, $user);
    $a2 = $this->service->register($session, $user);

    expect($a1->id)->toBe($a2->id);
    expect(TrainingAttendance::query()->where('training_session_id', $session->id)->count())->toBe(1);
});

it('refuses registration when capacity is full', function (): void {
    $plan = TrainingPlan::factory()->forStructure($this->structure)->create();
    $session = TrainingSession::factory()->forPlan($plan)->create(['capacity' => 1]);
    $u1 = User::factory()->forStructure($this->structure)->create();
    $u2 = User::factory()->forStructure($this->structure)->create();

    $this->service->register($session, $u1);

    expect(fn () => $this->service->register($session, $u2))->toThrow(HttpException::class);
});

it('re-registering after cancel revives the row to registered', function (): void {
    $plan = TrainingPlan::factory()->forStructure($this->structure)->create();
    $session = TrainingSession::factory()->forPlan($plan)->create(['capacity' => 5]);
    $user = User::factory()->forStructure($this->structure)->create();

    $a = $this->service->register($session, $user);
    $cancelled = $this->service->cancel($a);
    expect($cancelled->status)->toBe(TrainingAttendanceStatus::Cancelled);

    $reregistered = $this->service->register($session, $user);

    expect($reregistered->id)->toBe($a->id)
        ->and($reregistered->status)->toBe(TrainingAttendanceStatus::Registered)
        ->and($reregistered->cancelled_at)->toBeNull();
});

it('marks attended sets attended_at + status', function (): void {
    $plan = TrainingPlan::factory()->forStructure($this->structure)->create();
    $session = TrainingSession::factory()->forPlan($plan)->create();
    $user = User::factory()->forStructure($this->structure)->create();
    $a = $this->service->register($session, $user);

    $marked = $this->service->markAttended($a, notes: 'Ponctuel');

    expect($marked->status)->toBe(TrainingAttendanceStatus::Attended)
        ->and($marked->attended_at)->not->toBeNull()
        ->and($marked->notes)->toBe('Ponctuel');
});
