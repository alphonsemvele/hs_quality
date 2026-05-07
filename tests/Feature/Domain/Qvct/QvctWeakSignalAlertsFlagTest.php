<?php

declare(strict_types=1);

use App\Features\QvctWeakSignalAlerts;
use App\Jobs\NotifyReferentRhJob;
use App\Models\QvctCampaign;
use App\Models\QvctWeakSignal;
use App\Models\Structure;
use App\Models\User;
use App\Notifications\WeakSignalDetectedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;
use Laravel\Pennant\Feature;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $campaign = QvctCampaign::factory()->forStructure($this->structure)->create();
    $this->signal = QvctWeakSignal::factory()->forCampaign($campaign)->create();

    $this->rhUser = User::factory()->forStructure($this->structure)->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->structure->id);
    $this->rhUser->assignRole('rh');
});

it('sends WeakSignalDetectedNotification when the Pennant flag is on (default)', function (): void {
    Notification::fake();

    (new NotifyReferentRhJob($this->signal))->handle();

    Notification::assertSentTo($this->rhUser, WeakSignalDetectedNotification::class);
});

it('suppresses the mail fan-out when the Pennant flag is off for the structure', function (): void {
    Notification::fake();
    Feature::for($this->structure)->deactivate(QvctWeakSignalAlerts::class);

    (new NotifyReferentRhJob($this->signal))->handle();

    Notification::assertNothingSent();
});

it('does not leak deactivation across structures', function (): void {
    Notification::fake();

    $otherStructure = Structure::factory()->create();
    Feature::for($otherStructure)->deactivate(QvctWeakSignalAlerts::class);

    (new NotifyReferentRhJob($this->signal))->handle();

    Notification::assertSentTo($this->rhUser, WeakSignalDetectedNotification::class);
});
