<?php

declare(strict_types=1);

use App\Models\Structure;
use App\Models\User;
use App\Notifications\UserInvitedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

/**
 * #52 — UserController HTTP tests covering invite, list, show, deactivate,
 * reactivate, and the policy boundaries (who can do what).
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Notification::fake();
});

// ── INDEX ─────────────────────────────────────────────────────────────────────

it('lists tenant users for a dirigeant', function (): void {
    $dirigeant = actingAsRole('dirigeant');
    User::factory()->forStructure($dirigeant->structure)->count(3)->create();

    $this->get('/users')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/users/index')
            // 4 = dirigeant + 3 created
            ->has('users.data', 4),
        );
});

it('does not leak foreign-tenant users or platform admins into the directory', function (): void {
    $dirigeant = actingAsRole('dirigeant');
    User::factory()->forStructure($dirigeant->structure)->count(2)->create();

    // Noise that must NOT appear in this structure's directory.
    User::factory()->forStructure(Structure::factory()->create())->count(3)->create();
    User::factory()->create(['structure_id' => null, 'is_platform_admin' => true]);

    $this->get('/users')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/users/index')
            // only dirigeant + 2 in-structure users; foreign + platform admin excluded
            ->has('users.data', 3),
        );
});

it('refuses index to a coordinateur (no users.manage.structure permission)', function (): void {
    actingAsRole('coordinateur');

    $this->get('/users')->assertForbidden();
});

it('refuses index to an intervenant', function (): void {
    actingAsRole('intervenant');

    $this->get('/users')->assertForbidden();
});

// ── CREATE / STORE ────────────────────────────────────────────────────────────

it('invites a new coordinateur via POST and notifies them', function (): void {
    $dirigeant = actingAsRole('dirigeant');

    $this->post('/users', [
        'first_name' => 'Marie',
        'last_name' => 'Dupont',
        'email' => 'marie@example.fr',
        'type' => 'coordinateur',
    ])->assertRedirect();

    $invited = User::where('email', 'marie@example.fr')->firstOrFail();
    expect($invited->structure_id)->toBe($dirigeant->structure_id);
    Notification::assertSentTo($invited, UserInvitedNotification::class);
});

it('rejects POST with missing required fields', function (): void {
    actingAsRole('dirigeant');

    $this->from('/users/create')->post('/users', [])
        ->assertRedirect('/users/create')
        ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'type']);
});

it('rejects a duplicate email at validation', function (): void {
    actingAsRole('dirigeant');
    User::factory()->create(['email' => 'taken@x.fr']);

    $this->from('/users/create')->post('/users', [
        'first_name' => 'A', 'last_name' => 'B',
        'email' => 'taken@x.fr', 'type' => 'intervenant',
    ])->assertSessionHasErrors('email');
});

it('rejects POST from a coordinateur (lacks users.manage.structure)', function (): void {
    actingAsRole('coordinateur');

    $this->post('/users', [
        'first_name' => 'X', 'last_name' => 'Y',
        'email' => 'x@y.fr', 'type' => 'intervenant',
    ])->assertForbidden();

    expect(User::where('email', 'x@y.fr')->exists())->toBeFalse();
});

it('rejects inviting a portal beneficiary role from the admin surface', function (): void {
    actingAsRole('dirigeant');

    $this->post('/users', [
        'first_name' => 'P', 'last_name' => 'B',
        'email' => 'portal@y.fr', 'type' => 'beneficiaire_portal',
    ])->assertForbidden();
});

// ── SHOW ──────────────────────────────────────────────────────────────────────

it('shows a tenant user to a dirigeant', function (): void {
    $dirigeant = actingAsRole('dirigeant');
    $target = User::factory()->forStructure($dirigeant->structure)->create();

    $this->get("/users/{$target->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/users/show')
            ->where('user.id', $target->id),
        );
});

it('refuses show on a foreign-tenant user (cross-tenant boundary)', function (): void {
    actingAsRole('dirigeant');
    $foreign = User::factory()->forStructure(Structure::factory()->create())->create();

    $this->get("/users/{$foreign->id}")->assertForbidden();
});

// ── DEACTIVATE / REACTIVATE ───────────────────────────────────────────────────

it('deactivates a user via POST and revokes their tokens', function (): void {
    $dirigeant = actingAsRole('dirigeant');
    $target = User::factory()->forStructure($dirigeant->structure)->create();
    $target->createToken('mobile');

    $this->post("/users/{$target->id}/deactivate")->assertRedirect();

    expect($target->fresh()->status)->toBe('inactive');
    expect($target->tokens()->count())->toBe(0);
});

it('refuses self-deactivate (no locking yourself out)', function (): void {
    $dirigeant = actingAsRole('dirigeant');

    $this->post("/users/{$dirigeant->id}/deactivate")->assertForbidden();
    expect($dirigeant->fresh()->status)->toBe('active');
});

it('reactivates an inactive user', function (): void {
    $dirigeant = actingAsRole('dirigeant');
    $target = User::factory()->forStructure($dirigeant->structure)->create(['status' => 'inactive']);

    $this->post("/users/{$target->id}/reactivate")->assertRedirect();

    expect($target->fresh()->status)->toBe('active');
});

// ── PLATFORM ADMIN BOUNDARY ───────────────────────────────────────────────────

it('refuses index to a platform admin (managing tenants, not users)', function (): void {
    $platformAdmin = User::factory()->create([
        'structure_id' => null,
        'is_platform_admin' => true,
    ]);
    $this->actingAs($platformAdmin);

    // EnsureTenant binds nothing → query returns empty AND policy denies.
    // Either way: the surface is not theirs.
    $this->get('/users')->assertForbidden();
});
