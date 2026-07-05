<?php

declare(strict_types=1);

use App\Jobs\NotifyARSJob;
use App\Models\Incident;
use App\Models\Structure;
use App\Models\User;
use App\Notifications\Incidents\IncidentArsNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Cache::flush();
    Notification::fake();

    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->declarant = User::factory()->forStructure($this->structure)->create([
        'type' => 'intervenant',
    ]);

    $this->incident = Incident::factory()->declaredBy($this->declarant)->grave()->create();
});

it('sends the ARS notification when ARS email is configured and enabled', function (): void {
    config([
        'incidents.ars.enabled' => true,
        'incidents.ars.email' => 'ars-region@example.fr',
    ]);

    (new NotifyARSJob($this->incident))->handle();

    Notification::assertSentOnDemand(IncidentArsNotification::class);
    expect($this->incident->fresh()->notifie_ars_at)->not->toBeNull();
});

it('skips sending when the ARS feature is disabled but still flips the timestamp', function (): void {
    config([
        'incidents.ars.enabled' => false,
        'incidents.ars.email' => 'ars-region@example.fr',
    ]);

    (new NotifyARSJob($this->incident))->handle();

    Notification::assertNothingSent();
    expect($this->incident->fresh()->notifie_ars_at)->not->toBeNull();
});

it('skips sending when the ARS email is missing', function (): void {
    config([
        'incidents.ars.enabled' => true,
        'incidents.ars.email' => null,
    ]);

    (new NotifyARSJob($this->incident))->handle();

    Notification::assertNothingSent();
    expect($this->incident->fresh()->notifie_ars_at)->not->toBeNull();
});
