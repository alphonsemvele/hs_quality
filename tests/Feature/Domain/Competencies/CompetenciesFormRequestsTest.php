<?php

declare(strict_types=1);

use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\Competencies\AddSessionRequest;
use App\Http\Requests\Competencies\DraftTrainingPlanRequest;
use App\Http\Requests\Competencies\MarkAttendedRequest;
use App\Http\Requests\Competencies\RecordCertificationRequest;
use App\Http\Requests\Competencies\RecordHabilitationRequest;
use App\Http\Requests\Competencies\RegisterAttendanceRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    // Some rules use exists:users,id — make sure there's a user in the DB
    // for those tests to exercise the validation path under test.
    $this->existingUserId = User::factory()->create()->id;
});

function validateCompetencyRequest(string $requestClass, array $payload): Illuminate\Validation\Validator
{
    /** @var BaseFormRequest $req */
    $req = new $requestClass;

    return Validator::make($payload, $req->rules());
}

// ── RecordHabilitationRequest ────────────────────────────────────────────

it('RecordHabilitation requires user_id and type', function (): void {
    expect(validateCompetencyRequest(RecordHabilitationRequest::class, [])->fails())->toBeTrue();
    expect(validateCompetencyRequest(RecordHabilitationRequest::class, [
        'user_id' => $this->existingUserId,
        'type' => 'DEAS',
    ])->fails())->toBeFalse();
});

it('RecordHabilitation rejects valid_until before valid_from', function (): void {
    expect(validateCompetencyRequest(RecordHabilitationRequest::class, [
        'user_id' => $this->existingUserId,
        'type' => 'DEAS',
        'valid_from' => '2026-01-01',
        'valid_until' => '2025-01-01',
    ])->fails())->toBeTrue();
});

// ── RecordCertificationRequest ───────────────────────────────────────────

it('RecordCertification requires expires_at strictly after issued_on', function (): void {
    expect(validateCompetencyRequest(RecordCertificationRequest::class, [
        'user_id' => $this->existingUserId,
        'type' => 'BLS',
        'issued_on' => '2026-06-01',
        'expires_at' => '2026-06-01', // same day → invalid (after, not after_or_equal)
    ])->fails())->toBeTrue();

    expect(validateCompetencyRequest(RecordCertificationRequest::class, [
        'user_id' => $this->existingUserId,
        'type' => 'BLS',
        'issued_on' => '2026-06-01',
        'expires_at' => '2028-06-01',
    ])->fails())->toBeFalse();
});

// ── DraftTrainingPlanRequest ────────────────────────────────────────────

it('DraftTrainingPlan validates year range', function (): void {
    expect(validateCompetencyRequest(DraftTrainingPlanRequest::class, [
        'year' => 1999,
        'theme' => 'X',
    ])->fails())->toBeTrue();
    expect(validateCompetencyRequest(DraftTrainingPlanRequest::class, [
        'year' => 2026,
        'theme' => 'Bientraitance',
    ])->fails())->toBeFalse();
});

// ── AddSessionRequest ───────────────────────────────────────────────────

it('AddSession requires ends_at strictly after starts_at', function (): void {
    expect(validateCompetencyRequest(AddSessionRequest::class, [
        'title' => 'X',
        'starts_at' => '2026-06-01 09:00',
        'ends_at' => '2026-06-01 09:00',
        'capacity' => 10,
    ])->fails())->toBeTrue();

    expect(validateCompetencyRequest(AddSessionRequest::class, [
        'title' => 'X',
        'starts_at' => '2026-06-01 09:00',
        'ends_at' => '2026-06-01 12:00',
        'capacity' => 10,
    ])->fails())->toBeFalse();
});

it('AddSession validates capacity range', function (): void {
    expect(validateCompetencyRequest(AddSessionRequest::class, [
        'title' => 'X',
        'starts_at' => '2026-06-01 09:00',
        'ends_at' => '2026-06-01 12:00',
        'capacity' => 0,
    ])->fails())->toBeTrue();
});

// ── RegisterAttendance / MarkAttended ───────────────────────────────────

it('RegisterAttendance accepts an empty payload (self-service)', function (): void {
    expect(validateCompetencyRequest(RegisterAttendanceRequest::class, [])->fails())->toBeFalse();
});

it('MarkAttended optionally accepts notes', function (): void {
    expect(validateCompetencyRequest(MarkAttendedRequest::class, [])->fails())->toBeFalse();
    expect(validateCompetencyRequest(MarkAttendedRequest::class, ['notes' => 'Ponctuel'])->fails())->toBeFalse();
});
