<?php

declare(strict_types=1);

use App\Models\Certification;
use App\Models\Habilitation;
use App\Models\TrainingAttendance;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

// ── Habilitations ────────────────────────────────────────────────────────

it('records a habilitation as RH', function (): void {
    $rh = actingAsApiRole('rh');
    $intervenant = User::factory()->forStructure($rh->structure)->create();

    $this->postJson('/api/v1/competencies/habilitations', [
        'user_id' => $intervenant->id,
        'type' => 'DEAS',
        'reference_number' => 'RNCP-12345',
        'valid_from' => '2020-06-15',
    ])->assertCreated()->assertJsonPath('type', 'DEAS');
});

it('rejects intervenant trying to record a habilitation (lacks certifications.record)', function (): void {
    actingAsApiRole('intervenant');

    $this->postJson('/api/v1/competencies/habilitations', [
        'user_id' => 1,
        'type' => 'DEAS',
    ])->assertForbidden();
});

it('intervenant only sees their own habilitations on index', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    Habilitation::factory()->forUser($intervenant)->count(2)->create();

    $other = User::factory()->forStructure($intervenant->structure)->create();
    Habilitation::factory()->forUser($other)->count(3)->create();

    $this->getJson('/api/v1/competencies/habilitations')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('coordinateur sees the structure-wide habilitation list', function (): void {
    $coord = actingAsApiRole('coordinateur');
    Habilitation::factory()->forStructure($coord->structure)->count(4)->create();

    $this->getJson('/api/v1/competencies/habilitations')
        ->assertOk()
        ->assertJsonCount(4, 'data');
});

// ── Certifications ───────────────────────────────────────────────────────

it('records a certification with strict expires_at > issued_on', function (): void {
    $rh = actingAsApiRole('rh');
    $intervenant = User::factory()->forStructure($rh->structure)->create();

    $this->postJson('/api/v1/competencies/certifications', [
        'user_id' => $intervenant->id,
        'type' => 'BLS',
        'issued_on' => '2026-06-01',
        'expires_at' => '2028-06-01',
    ])->assertCreated();
});

it('rejects certification when expires_at equals issued_on', function (): void {
    $rh = actingAsApiRole('rh');
    $intervenant = User::factory()->forStructure($rh->structure)->create();

    $this->postJson('/api/v1/competencies/certifications', [
        'user_id' => $intervenant->id,
        'type' => 'BLS',
        'issued_on' => '2026-06-01',
        'expires_at' => '2026-06-01',
    ])->assertUnprocessable()->assertJsonValidationErrors(['expires_at']);
});

it('cannot view another tenant certification (cross-tenant guard)', function (): void {
    $coord = actingAsApiRole('coordinateur');

    $otherCoord = actingAsApiRole('coordinateur'); // switches to a new structure
    $foreignCert = Certification::factory()->forStructure($coord->structure)->create();

    // Now logged in as otherCoord — should 404 because route binding is
    // tenant-scoped (BelongsToStructure global scope).
    $this->getJson("/api/v1/competencies/certifications/{$foreignCert->id}")
        ->assertNotFound();
});

// ── Training plans + sessions ────────────────────────────────────────────

it('drafts and publishes a training plan', function (): void {
    $rh = actingAsApiRole('rh');

    $created = $this->postJson('/api/v1/competencies/training-plans', [
        'year' => 2026,
        'theme' => 'Bientraitance',
    ])->assertCreated();
    $planId = $created->json('id');

    $this->postJson("/api/v1/competencies/training-plans/{$planId}/publish")
        ->assertOk()
        ->assertJsonPath('status', 'published');
});

it('intervenant cannot draft a plan', function (): void {
    actingAsApiRole('intervenant');

    $this->postJson('/api/v1/competencies/training-plans', [
        'year' => 2026,
        'theme' => 'X',
    ])->assertForbidden();
});

it('adds a session to a draft plan', function (): void {
    $rh = actingAsApiRole('rh');
    $plan = TrainingPlan::factory()->forStructure($rh->structure)->create();

    $this->postJson("/api/v1/competencies/training-plans/{$plan->id}/sessions", [
        'title' => 'BLS module 1',
        'starts_at' => '2026-06-15 09:00:00',
        'ends_at' => '2026-06-15 12:00:00',
        'capacity' => 10,
    ])->assertCreated();
});

// ── Attendances ──────────────────────────────────────────────────────────

it('intervenant self-registers for a session', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $plan = TrainingPlan::factory()->forStructure($intervenant->structure)->create();
    $session = TrainingSession::factory()->forPlan($plan)->create();

    $this->postJson("/api/v1/competencies/training-sessions/{$session->id}/register")
        ->assertCreated()
        ->assertJsonPath('user_id', $intervenant->id)
        ->assertJsonPath('status', 'registered');
});

it('rejects mark-attended by intervenant (lacks trainings.record)', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $plan = TrainingPlan::factory()->forStructure($intervenant->structure)->create();
    $session = TrainingSession::factory()->forPlan($plan)->create();
    $attendance = TrainingAttendance::factory()->forSession($session, $intervenant)->create();

    $this->postJson("/api/v1/competencies/training-attendances/{$attendance->id}/mark-attended")
        ->assertForbidden();
});

it('coordinateur marks attendance as attended', function (): void {
    $coord = actingAsApiRole('coordinateur');
    $intervenant = User::factory()->forStructure($coord->structure)->create();
    $plan = TrainingPlan::factory()->forStructure($coord->structure)->create();
    $session = TrainingSession::factory()->forPlan($plan)->create();
    $attendance = TrainingAttendance::factory()->forSession($session, $intervenant)->create();

    $this->postJson("/api/v1/competencies/training-attendances/{$attendance->id}/mark-attended", [
        'notes' => 'Ponctuel',
    ])->assertOk()
        ->assertJsonPath('status', 'attended');
});

it('intervenant can self-cancel their own attendance', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $plan = TrainingPlan::factory()->forStructure($intervenant->structure)->create();
    $session = TrainingSession::factory()->forPlan($plan)->create();
    $attendance = TrainingAttendance::factory()->forSession($session, $intervenant)->create();

    $this->postJson("/api/v1/competencies/training-attendances/{$attendance->id}/cancel")
        ->assertOk()
        ->assertJsonPath('status', 'cancelled');
});

it('requires authentication for all competencies endpoints', function (): void {
    $this->getJson('/api/v1/competencies/habilitations')->assertUnauthorized();
    $this->getJson('/api/v1/competencies/certifications')->assertUnauthorized();
    $this->getJson('/api/v1/competencies/training-plans')->assertUnauthorized();
});
