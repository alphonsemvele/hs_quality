<?php

declare(strict_types=1);

use App\Jobs\NotifyResponsableSecteurJob;
use App\Models\Incident;
use App\Models\Structure;
use App\Models\User;
use App\Notifications\Incidents\IncidentSeverelyDeclaredNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Notification::fake();

    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->declarant = User::factory()->forStructure($this->structure)->create([
        'type' => 'intervenant',
    ]);

    $this->incident = Incident::factory()->declaredBy($this->declarant)->grave()->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->structure->id);
});

it('notifies dirigeants, coordinateurs and référents qualité when a grave incident is declared', function (): void {
    $dirigeant = User::factory()->forStructure($this->structure)->create(['type' => 'dirigeant']);
    $dirigeant->assignRole('dirigeant');

    $coord = User::factory()->forStructure($this->structure)->create(['type' => 'coordinateur']);
    $coord->assignRole('coordinateur');

    $referent = User::factory()->forStructure($this->structure)->create(['type' => 'referent_qualite']);
    $referent->assignRole('referent_qualite');

    $intervenant = User::factory()->forStructure($this->structure)->create(['type' => 'intervenant']);
    $intervenant->assignRole('intervenant');

    (new NotifyResponsableSecteurJob($this->incident))->handle();

    Notification::assertSentTo($dirigeant, IncidentSeverelyDeclaredNotification::class);
    Notification::assertSentTo($coord, IncidentSeverelyDeclaredNotification::class);
    Notification::assertSentTo($referent, IncidentSeverelyDeclaredNotification::class);
    Notification::assertNotSentTo($intervenant, IncidentSeverelyDeclaredNotification::class);

    expect($this->incident->fresh()->notifie_responsable_at)->not->toBeNull();
});

it('is idempotent on the notifie_responsable_at timestamp', function (): void {
    $dirigeant = User::factory()->forStructure($this->structure)->create(['type' => 'dirigeant']);
    $dirigeant->assignRole('dirigeant');

    $this->incident->update(['notifie_responsable_at' => now()->subHour()]);

    (new NotifyResponsableSecteurJob($this->incident))->handle();

    Notification::assertNothingSent();
});

it('does not leak notifications to responsables of another structure', function (): void {
    $otherStructure = Structure::factory()->create();
    $alien = User::factory()->forStructure($otherStructure)->create(['type' => 'dirigeant']);

    app(PermissionRegistrar::class)->setPermissionsTeamId($otherStructure->id);
    $alien->assignRole('dirigeant');

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->structure->id);

    (new NotifyResponsableSecteurJob($this->incident))->handle();

    Notification::assertNotSentTo($alien, IncidentSeverelyDeclaredNotification::class);
});

it('still flips the notified timestamp when no responsable user exists', function (): void {
    (new NotifyResponsableSecteurJob($this->incident))->handle();

    Notification::assertNothingSent();
    expect($this->incident->fresh()->notifie_responsable_at)->not->toBeNull();
});
