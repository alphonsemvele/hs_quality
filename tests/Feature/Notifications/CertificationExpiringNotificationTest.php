<?php

declare(strict_types=1);

use App\Jobs\CertificationExpiryAlertJob;
use App\Models\Certification;
use App\Notifications\CertificationExpiringNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Notification::fake();
});

it('notifies the cert owner when a window advances', function (): void {
    $intervenant = actingAsRole('intervenant');
    $cert = Certification::factory()
        ->forUser($intervenant)
        ->expiresInDays(60)
        ->create(['last_alert_window' => null]);

    (new CertificationExpiryAlertJob)->handle();

    Notification::assertSentTo(
        $intervenant,
        CertificationExpiringNotification::class,
        function (CertificationExpiringNotification $n) use ($cert) {
            return $n->certification->is($cert) && $n->window === 'T-90';
        },
    );
});

it('also notifies coordinateur, RH, and dirigeant of the same structure', function (): void {
    $intervenant = actingAsRole('intervenant');
    $coord = actingAsRole('coordinateur', $intervenant->structure);
    $rh = actingAsRole('rh', $intervenant->structure);
    $dirigeant = actingAsRole('dirigeant', $intervenant->structure);

    Certification::factory()
        ->forUser($intervenant)
        ->expiresInDays(60)
        ->create(['last_alert_window' => null]);

    (new CertificationExpiryAlertJob)->handle();

    Notification::assertSentTo($intervenant, CertificationExpiringNotification::class);
    Notification::assertSentTo($coord, CertificationExpiringNotification::class);
    Notification::assertSentTo($rh, CertificationExpiringNotification::class);
    Notification::assertSentTo($dirigeant, CertificationExpiringNotification::class);
});

it('does not notify users from a different structure', function (): void {
    $intervenant = actingAsRole('intervenant');
    $foreignRh = actingAsRole('rh'); // different structure

    Certification::factory()
        ->forUser($intervenant)
        ->expiresInDays(60)
        ->create(['last_alert_window' => null]);

    (new CertificationExpiryAlertJob)->handle();

    Notification::assertNothingSentTo($foreignRh);
});

it('does not notify on second run for the same window (idempotent)', function (): void {
    $intervenant = actingAsRole('intervenant');

    Certification::factory()
        ->forUser($intervenant)
        ->expiresInDays(60)
        ->create(['last_alert_window' => null]);

    (new CertificationExpiryAlertJob)->handle();
    Notification::assertSentToTimes($intervenant, CertificationExpiringNotification::class, 1);

    // Second call — same day, same window → no extra notification.
    (new CertificationExpiryAlertJob)->handle();
    Notification::assertSentToTimes($intervenant, CertificationExpiringNotification::class, 1);
});

it('sends a fresh notification when the window advances', function (): void {
    $intervenant = actingAsRole('intervenant');

    Certification::factory()
        ->forUser($intervenant)
        ->expiresInDays(60)
        ->create(['last_alert_window' => 'T-90']);

    // Travel past the T-30 boundary
    $this->travel(31)->days();

    (new CertificationExpiryAlertJob)->handle();

    Notification::assertSentTo(
        $intervenant,
        CertificationExpiringNotification::class,
        fn (CertificationExpiringNotification $n) => $n->window === 'T-30',
    );
});

it('uses owner-centric language for the cert owner', function (): void {
    $intervenant = actingAsRole('intervenant');
    $cert = Certification::factory()
        ->forUser($intervenant)
        ->expiresInDays(20)
        ->create(['last_alert_window' => null, 'type' => 'BLS']);

    $mail = (new CertificationExpiringNotification($cert, 'T-30'))->toMail($intervenant);
    $rendered = $mail->toArray();

    expect($rendered['subject'])->toContain('30 jours');
    expect(implode(' ', $rendered['introLines']))->toContain('Votre certification BLS');
});

it('uses third-person language for non-owners', function (): void {
    $intervenant = actingAsRole('intervenant');
    $intervenant->update(['first_name' => 'Marie', 'last_name' => 'Leclerc']);
    $rh = actingAsRole('rh', $intervenant->structure);
    $cert = Certification::factory()
        ->forUser($intervenant)
        ->expiresInDays(20)
        ->create(['type' => 'BLS']);

    $mail = (new CertificationExpiringNotification($cert, 'T-30'))->toMail($rh);
    $rendered = $mail->toArray();

    expect(implode(' ', $rendered['introLines']))
        ->toContain('Marie Leclerc')
        ->toContain('certification BLS');
});
