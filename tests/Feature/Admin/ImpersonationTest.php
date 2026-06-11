<?php

declare(strict_types=1);

use App\Models\ImpersonationLog;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

// ─── helpers ─────────────────────────────────────────────────────────────────

function createPlatformAdmin(): User
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

function createTenantUser(Structure $structure): User
{
    return User::factory()->forStructure($structure)->state(['type' => 'coordinateur'])->create();
}

// ─── start impersonation ─────────────────────────────────────────────────────

it('platform admin can start impersonation of a tenant user', function (): void {
    $admin = createPlatformAdmin();
    $structure = Structure::factory()->create();
    $target = createTenantUser($structure);

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$target->id}", ['reason' => 'Vérification signalement utilisateur'])
        ->assertRedirect('/dashboard');

    $log = ImpersonationLog::first();
    expect($log)->not->toBeNull()
        ->and($log->impersonator_id)->toBe($admin->id)
        ->and($log->impersonated_user_id)->toBe($target->id)
        ->and($log->structure_id)->toBe($structure->id)
        ->and($log->reason)->toBe('Vérification signalement utilisateur')
        ->and($log->stopped_at)->toBeNull();
});

it('sets the impersonating_as session key on start', function (): void {
    $admin = createPlatformAdmin();
    $structure = Structure::factory()->create(['name' => 'SAAD Lyon Test']);
    $target = createTenantUser($structure);

    $response = $this->actingAs($admin)
        ->post("/admin/impersonate/{$target->id}", ['reason' => 'Audit compte utilisateur']);

    $response->assertSessionHas('impersonating_as', fn ($payload) => $payload['user_id'] === $target->id &&
        $payload['structure_id'] === $structure->id
    );
});

it('records ip_address and started_at on the impersonation log', function (): void {
    $admin = createPlatformAdmin();
    $structure = Structure::factory()->create();
    $target = createTenantUser($structure);

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$target->id}", ['reason' => 'Support technique urgence'])
        ->assertRedirect();

    $log = ImpersonationLog::first();
    expect($log->ip_address)->toBe('127.0.0.1')
        ->and($log->started_at)->not->toBeNull();
});

// ─── stop impersonation ───────────────────────────────────────────────────────

it('platform admin can stop an active impersonation session', function (): void {
    $admin = createPlatformAdmin();
    $structure = Structure::factory()->create();
    $target = createTenantUser($structure);

    // Start
    $this->actingAs($admin)
        ->post("/admin/impersonate/{$target->id}", ['reason' => 'Test arrêt session']);

    $log = ImpersonationLog::first();
    expect($log->stopped_at)->toBeNull();

    // Stop
    $this->actingAs($admin)
        ->post('/admin/impersonate/stop')
        ->assertRedirect('/admin');

    expect($log->fresh()->stopped_at)->not->toBeNull();
});

it('clears the impersonating_as session key on stop', function (): void {
    $admin = createPlatformAdmin();
    $structure = Structure::factory()->create();
    $target = createTenantUser($structure);

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$target->id}", ['reason' => 'Test nettoyage session']);

    $this->actingAs($admin)
        ->post('/admin/impersonate/stop')
        ->assertSessionMissing('impersonating_as');
});

it('stop without an active session is a no-op (does not crash)', function (): void {
    $admin = createPlatformAdmin();

    $this->actingAs($admin)
        ->post('/admin/impersonate/stop')
        ->assertRedirect('/admin');

    expect(ImpersonationLog::count())->toBe(0);
});

// ─── authorization guards ─────────────────────────────────────────────────────

it('tenant-scoped user cannot start impersonation (404 — surface invisible)', function (): void {
    $structure = Structure::factory()->create();
    $attacker = createTenantUser($structure);
    $victim = createTenantUser($structure);

    $this->actingAs($attacker)
        ->post("/admin/impersonate/{$victim->id}", ['reason' => 'Test'])
        ->assertNotFound();
});

it('cannot impersonate another platform admin', function (): void {
    $admin = createPlatformAdmin();
    $otherAdmin = createPlatformAdmin();

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$otherAdmin->id}", ['reason' => 'Test plateforme'])
        ->assertForbidden();
});

it('cannot impersonate yourself', function (): void {
    $admin = createPlatformAdmin();

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$admin->id}", ['reason' => 'Test auto-impersonation interdite'])
        ->assertForbidden();
});

it('cannot impersonate a user with no structure_id', function (): void {
    $admin = createPlatformAdmin();
    $userWithoutStructure = User::factory()->create([
        'structure_id' => null,
        'is_platform_admin' => false,
        'type' => null,
    ]);

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$userWithoutStructure->id}", ['reason' => 'Test sans structure'])
        ->assertStatus(422);
});

// ─── validation ──────────────────────────────────────────────────────────────

it('rejects impersonation without a reason', function (): void {
    $admin = createPlatformAdmin();
    $structure = Structure::factory()->create();
    $target = createTenantUser($structure);

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$target->id}", [])
        ->assertSessionHasErrors('reason');

    expect(ImpersonationLog::count())->toBe(0);
});

it('rejects a reason shorter than 10 characters', function (): void {
    $admin = createPlatformAdmin();
    $structure = Structure::factory()->create();
    $target = createTenantUser($structure);

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$target->id}", ['reason' => 'Court'])
        ->assertSessionHasErrors('reason');

    expect(ImpersonationLog::count())->toBe(0);
});

// ─── unauthenticated ─────────────────────────────────────────────────────────

it('unauthenticated requests to impersonate redirect to login', function (): void {
    $structure = Structure::factory()->create();
    $target = createTenantUser($structure);

    $this->post("/admin/impersonate/{$target->id}", ['reason' => 'Test sans auth'])
        ->assertRedirect('/login');
});
