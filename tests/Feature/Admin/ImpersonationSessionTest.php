<?php

declare(strict_types=1);

use App\Models\ImpersonationLog;
use App\Models\Incident;
use App\Models\Structure;
use App\Models\User;
use App\Support\ImpersonationSession;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function impersonationPlatformAdmin(): User
{
    return User::factory()->create([
        'first_name' => 'Op',
        'last_name' => 'Admin',
        'email' => 'op'.uniqid().'@platform.fr',
        'password' => Hash::make('password'),
        'structure_id' => null,
        'is_platform_admin' => true,
        'type' => null,
    ]);
}

function impersonationCoordinateur(Structure $structure): User
{
    $user = User::factory()->forStructure($structure)->state(['type' => 'coordinateur'])->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());
    $user->assignRole('coordinateur');

    return $user;
}

// ─── permission delegation (BasePolicy + User::hasPermissionTo) ──────────────

it('impersonating admin inherits the target users own permissions to act on a tenant model', function (): void {
    $admin = impersonationPlatformAdmin();
    $structure = Structure::factory()->create();
    $target = impersonationCoordinateur($structure);

    $incident = Incident::factory()
        ->forStructure($structure)
        ->declaredBy($target)
        ->enAnalyse()
        ->create();

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$target->id}", ['reason' => 'Vérification traitement incident'])
        ->assertRedirect('/dashboard');

    $this->post("/incidents/{$incident->id}/analyse", [
        'analyse_causes' => 'Pourquoi 1: sol mouillé. Pourquoi 2: absence de signalisation.',
    ])->assertRedirect();

    expect($incident->fresh()->analyse_causes)->not->toBeNull();
});

it('impersonating admin is blocked from a structure other than the impersonated users own', function (): void {
    $admin = impersonationPlatformAdmin();
    $structure = Structure::factory()->create();
    $otherStructure = Structure::factory()->create();
    $target = impersonationCoordinateur($structure);

    $foreignIncident = Incident::factory()
        ->forStructure($otherStructure)
        ->enAnalyse()
        ->create();

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$target->id}", ['reason' => 'Vérification traitement incident']);

    $this->post("/incidents/{$foreignIncident->id}/analyse", [
        'analyse_causes' => 'Tentative cross-tenant.',
    ])->assertNotFound();
});

// ─── audit impersonator_id stamping ───────────────────────────────────────────

it('stamps impersonator_id on audit entries created while impersonating', function (): void {
    $admin = impersonationPlatformAdmin();
    $structure = Structure::factory()->create();
    $target = impersonationCoordinateur($structure);

    $incident = Incident::factory()
        ->forStructure($structure)
        ->declaredBy($target)
        ->enAnalyse()
        ->create();

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$target->id}", ['reason' => 'Audit avec traçabilité impersonator_id']);

    $this->post("/incidents/{$incident->id}/analyse", [
        'analyse_causes' => 'Pourquoi 1: sol mouillé.',
    ])->assertRedirect();

    $audit = $incident->fresh()->audits()->where('event', 'updated')->latest('id')->first();

    expect($audit)->not->toBeNull()
        ->and($audit->impersonator_id)->toBe($admin->id)
        ->and($audit->user_id)->toBe($admin->id);
});

it('does not stamp impersonator_id on audits created by a regular tenant user', function (): void {
    $structure = Structure::factory()->create();
    $target = impersonationCoordinateur($structure);

    $incident = Incident::factory()
        ->forStructure($structure)
        ->declaredBy($target)
        ->enAnalyse()
        ->create();

    app()->instance('current_structure', $structure);

    $this->actingAs($target)
        ->post("/incidents/{$incident->id}/analyse", [
            'analyse_causes' => 'Pourquoi 1: sol mouillé.',
        ])->assertRedirect();

    $audit = $incident->fresh()->audits()->where('event', 'updated')->latest('id')->first();

    expect($audit)->not->toBeNull()
        ->and($audit->impersonator_id)->toBeNull();
});

// ─── 4-hour hard timeout ──────────────────────────────────────────────────────

it('auto-expires an impersonation session past the 4-hour cap', function (): void {
    Carbon::setTestNow('2026-06-01 09:00:00');

    $admin = impersonationPlatformAdmin();
    $structure = Structure::factory()->create();
    $target = impersonationCoordinateur($structure);

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$target->id}", ['reason' => 'Test expiration session 4h']);

    $log = ImpersonationLog::first();
    expect($log->stopped_at)->toBeNull();

    Carbon::setTestNow(Carbon::parse('2026-06-01 09:00:00')->addMinutes(ImpersonationSession::MAX_DURATION_MINUTES + 1));

    $this->get('/dashboard')->assertRedirect('/admin');

    expect(ImpersonationSession::current())->toBeNull()
        ->and($log->fresh()->stopped_at)->not->toBeNull();
});

it('keeps an impersonation session alive before the 4-hour cap is reached', function (): void {
    Carbon::setTestNow('2026-06-01 09:00:00');

    $admin = impersonationPlatformAdmin();
    $structure = Structure::factory()->create();
    $target = impersonationCoordinateur($structure);

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$target->id}", ['reason' => 'Test session encore active']);

    Carbon::setTestNow(Carbon::parse('2026-06-01 09:00:00')->addMinutes(ImpersonationSession::MAX_DURATION_MINUTES - 1));

    expect(ImpersonationSession::current())->not->toBeNull();
});
