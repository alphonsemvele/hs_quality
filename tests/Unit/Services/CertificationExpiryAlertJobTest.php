<?php

declare(strict_types=1);

use App\Jobs\CertificationExpiryAlertJob;
use App\Models\Certification;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
    $this->user = User::factory()->forStructure($this->structure)->create();
});

// ── windowFor() — pure function, the heart of the invariant ─────────────

it('maps days-until-expiry to the right window', function (int $days, ?string $window): void {
    expect(CertificationExpiryAlertJob::windowFor($days))->toBe($window);
})->with([
    'far in future (no window)' => [120, null],
    'edge of T-90 (90 days)' => [90, 'T-90'],
    'inside T-90 (60 days)' => [60, 'T-90'],
    'edge of T-30 (30 days)' => [30, 'T-30'],
    'inside T-30 (20 days)' => [20, 'T-30'],
    'edge of T-7 (7 days)' => [7, 'T-7'],
    'inside T-7 (3 days)' => [3, 'T-7'],
    'expires today (0 days)' => [0, 'T-7'],
    'expired yesterday (-1 days)' => [-1, 'expired'],
    'long expired (-100 days)' => [-100, 'expired'],
]);

it('boundary cases are inclusive on the upper bound', function (): void {
    // Day 91 → null, Day 90 → 'T-90'
    expect(CertificationExpiryAlertJob::windowFor(91))->toBeNull();
    expect(CertificationExpiryAlertJob::windowFor(90))->toBe('T-90');
});

// ── shouldAdvance() — idempotence guard ─────────────────────────────────

it('advances on first alert (no previous)', function (): void {
    expect(CertificationExpiryAlertJob::shouldAdvance(null, 'T-90'))->toBeTrue();
});

it('advances forward through the ladder', function (): void {
    expect(CertificationExpiryAlertJob::shouldAdvance('T-90', 'T-30'))->toBeTrue();
    expect(CertificationExpiryAlertJob::shouldAdvance('T-30', 'T-7'))->toBeTrue();
    expect(CertificationExpiryAlertJob::shouldAdvance('T-7', 'expired'))->toBeTrue();
});

it('does not re-fire the same window', function (): void {
    expect(CertificationExpiryAlertJob::shouldAdvance('T-90', 'T-90'))->toBeFalse();
    expect(CertificationExpiryAlertJob::shouldAdvance('T-30', 'T-30'))->toBeFalse();
    expect(CertificationExpiryAlertJob::shouldAdvance('expired', 'expired'))->toBeFalse();
});

it('refuses to walk backward (clock skew protection)', function (): void {
    expect(CertificationExpiryAlertJob::shouldAdvance('T-30', 'T-90'))->toBeFalse();
    expect(CertificationExpiryAlertJob::shouldAdvance('T-7', 'T-30'))->toBeFalse();
    expect(CertificationExpiryAlertJob::shouldAdvance('expired', 'T-7'))->toBeFalse();
});

// ── handle() — integration through the job + DB ─────────────────────────

it('marks T-90 on first sweep for a cert expiring in 60 days', function (): void {
    $cert = Certification::factory()->forUser($this->user)->expiresInDays(60)->create([
        'last_alert_window' => null,
    ]);

    (new CertificationExpiryAlertJob)->handle();

    $cert->refresh();
    expect($cert->last_alert_window)->toBe('T-90');
    expect($cert->last_alerted_at)->not->toBeNull();
});

it('re-running the same day is a no-op', function (): void {
    $cert = Certification::factory()->forUser($this->user)->expiresInDays(60)->create([
        'last_alert_window' => null,
    ]);

    (new CertificationExpiryAlertJob)->handle();
    $firstAlertedAt = $cert->fresh()->last_alerted_at;

    // Sleep 1 second to ensure the next now() differs
    $this->travel(1)->seconds();
    (new CertificationExpiryAlertJob)->handle();

    $cert->refresh();
    expect($cert->last_alert_window)->toBe('T-90')
        ->and($cert->last_alerted_at?->toIso8601String())->toBe($firstAlertedAt?->toIso8601String());
});

it('advances from T-90 to T-30 when the cert crosses the boundary', function (): void {
    $cert = Certification::factory()->forUser($this->user)->expiresInDays(60)->create([
        'last_alert_window' => 'T-90',
    ]);

    // Simulate 31 days passing — cert now at T-29
    $this->travel(31)->days();

    (new CertificationExpiryAlertJob)->handle();

    expect($cert->fresh()->last_alert_window)->toBe('T-30');
});

it('skips certs more than 90 days from expiry', function (): void {
    $cert = Certification::factory()->forUser($this->user)->expiresInDays(180)->create([
        'last_alert_window' => null,
    ]);

    (new CertificationExpiryAlertJob)->handle();

    expect($cert->fresh()->last_alert_window)->toBeNull();
});

it('marks expired certs as expired (no T-90/T-30/T-7 backfill)', function (): void {
    $cert = Certification::factory()->forUser($this->user)->expiresInDays(-5)->create([
        'last_alert_window' => null,
    ]);

    (new CertificationExpiryAlertJob)->handle();

    expect($cert->fresh()->last_alert_window)->toBe('expired');
});
