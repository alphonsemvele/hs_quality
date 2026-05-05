<?php

declare(strict_types=1);

use App\Enums\QvctWeakSignalType;
use App\Jobs\NotifyReferentRhJob;
use App\Models\QvctWeakSignal;
use App\Notifications\WeakSignalDetectedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Notification::fake();
});

it('notifies RH, dirigeant and référent qualité in the signal structure', function (): void {
    $rh = actingAsRole('rh');
    $dirigeant = actingAsRole('dirigeant', $rh->structure);
    $referent = actingAsRole('referent_qualite', $rh->structure);
    $intervenant = actingAsRole('intervenant', $rh->structure);

    $signal = QvctWeakSignal::factory()->create([
        'structure_id' => $rh->structure_id,
        'team_tag' => 'Paris Centre',
        'signal_type' => QvctWeakSignalType::BaisseMorale,
        'severity' => 4,
        'details' => ['mean_score' => 1.8],
    ]);

    (new NotifyReferentRhJob($signal))->handle();

    Notification::assertSentTo($rh, WeakSignalDetectedNotification::class);
    Notification::assertSentTo($dirigeant, WeakSignalDetectedNotification::class);
    Notification::assertSentTo($referent, WeakSignalDetectedNotification::class);
    // Intervenants should NOT receive — they have no business with weak-signal triage.
    Notification::assertNotSentTo($intervenant, WeakSignalDetectedNotification::class);
});

it('does not notify RH from a different structure (no cross-tenant leak)', function (): void {
    $rhA = actingAsRole('rh');
    $rhB = actingAsRole('rh'); // different structure (helper creates a new one when none passed)

    $signal = QvctWeakSignal::factory()->create([
        'structure_id' => $rhA->structure_id,
        'team_tag' => 'Lyon Est',
        'signal_type' => QvctWeakSignalType::Surcharge,
        'severity' => 3,
        'details' => ['mean_score' => 2.1],
    ]);

    (new NotifyReferentRhJob($signal))->handle();

    Notification::assertSentTo($rhA, WeakSignalDetectedNotification::class);
    Notification::assertNotSentTo($rhB, WeakSignalDetectedNotification::class);
});

it('does not include any individual respondent identity in the mail body', function (): void {
    $rh = actingAsRole('rh');

    $signal = QvctWeakSignal::factory()->create([
        'structure_id' => $rh->structure_id,
        'team_tag' => 'Marseille',
        'signal_type' => QvctWeakSignalType::Surcharge,
        'severity' => 5,
        'details' => ['mean_score' => 1.5, 'sample_size' => 6],
    ]);

    $mail = (new WeakSignalDetectedNotification($signal))->toMail($rh);
    $rendered = $mail->toArray();
    $bodyText = implode(' ', $rendered['introLines']).' '.implode(' ', $rendered['outroLines']);

    expect($bodyText)
        ->toContain('Marseille')           // team tag is OK to surface
        ->toContain('anonymat')             // privacy reaffirmation present
        ->not->toContain($rh->first_name)   // no individual names
        ->not->toContain($rh->last_name);
});

it('shapes the urgency line by severity', function (): void {
    $rh = actingAsRole('rh');

    foreach ([1 => 'À surveiller', 3 => 'court terme', 5 => 'prioritaire'] as $sev => $expectedFragment) {
        $signal = QvctWeakSignal::factory()->create([
            'structure_id' => $rh->structure_id,
            'team_tag' => 'X',
            'signal_type' => QvctWeakSignalType::BaisseMorale,
            'severity' => $sev,
            'details' => ['mean_score' => 1.0],
        ]);

        $rendered = (new WeakSignalDetectedNotification($signal))->toMail($rh)->toArray();
        $bodyText = implode(' ', $rendered['introLines']);

        expect($bodyText)->toContain($expectedFragment);
    }
});

it('handles a structure-wide signal with null team_tag', function (): void {
    $rh = actingAsRole('rh');

    $signal = QvctWeakSignal::factory()->create([
        'structure_id' => $rh->structure_id,
        'team_tag' => null,
        'signal_type' => QvctWeakSignalType::Surcharge,
        'severity' => 4,
        'details' => ['mean_score' => 1.9],
    ]);

    (new NotifyReferentRhJob($signal))->handle();

    Notification::assertSentTo($rh, WeakSignalDetectedNotification::class);
    $mail = (new WeakSignalDetectedNotification($signal))->toMail($rh);
    expect($mail->toArray()['subject'])->toContain('structure entière');
});
