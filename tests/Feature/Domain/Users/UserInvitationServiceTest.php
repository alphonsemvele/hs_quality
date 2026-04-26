<?php

declare(strict_types=1);

use App\Enums\UserType;
use App\Models\User;
use App\Notifications\UserInvitedNotification;
use App\Services\UserInvitationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

/**
 * #52 — UserInvitationService unit-style tests.
 *
 * The service is the single source of truth for "create user + assign
 * tenant-scoped role + queue invite email". Both the web controller and
 * any future API counterpart delegate to it.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('creates a user scoped to the given structure with the requested role', function (): void {
    Notification::fake();
    $dirigeant = actingAsRole('dirigeant');

    $result = app(UserInvitationService::class)->invite(
        data: [
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'email' => 'marie@example.fr',
            'type' => UserType::Coordinateur,
        ],
        structure: $dirigeant->structure,
        invitedBy: $dirigeant,
    );

    $user = $result['user'];
    expect($user->structure_id)->toBe($dirigeant->structure_id);
    expect($user->email)->toBe('marie@example.fr');
    expect($user->type)->toBe(UserType::Coordinateur);
    expect($user->email_verified_at)->toBeNull();

    app(PermissionRegistrar::class)->setPermissionsTeamId($dirigeant->structure_id);
    expect($user->fresh()->hasRole('coordinateur'))->toBeTrue();
});

it('lowercases the email at write time', function (): void {
    Notification::fake();
    $dirigeant = actingAsRole('dirigeant');

    $result = app(UserInvitationService::class)->invite(
        data: [
            'first_name' => 'A',
            'last_name' => 'B',
            'email' => 'MIXED@CASE.fr',
            'type' => UserType::Intervenant,
        ],
        structure: $dirigeant->structure,
        invitedBy: $dirigeant,
    );

    expect($result['user']->email)->toBe('mixed@case.fr');
});

it('returns a password-reset URL the inviter can hand over', function (): void {
    Notification::fake();
    $dirigeant = actingAsRole('dirigeant');

    $result = app(UserInvitationService::class)->invite(
        data: [
            'first_name' => 'A', 'last_name' => 'B', 'email' => 'x@y.fr',
            'type' => UserType::Intervenant,
        ],
        structure: $dirigeant->structure,
        invitedBy: $dirigeant,
    );

    expect($result['invitation_url'])->toBeString();
    expect($result['invitation_url'])->toContain('reset-password');
});

it('sends a UserInvitedNotification by email', function (): void {
    Notification::fake();
    $dirigeant = actingAsRole('dirigeant');

    $result = app(UserInvitationService::class)->invite(
        data: [
            'first_name' => 'A', 'last_name' => 'B', 'email' => 'mailme@y.fr',
            'type' => UserType::Intervenant,
        ],
        structure: $dirigeant->structure,
        invitedBy: $dirigeant,
    );

    Notification::assertSentTo($result['user'], UserInvitedNotification::class);
});

it('rolls back if the email already exists', function (): void {
    Notification::fake();
    $dirigeant = actingAsRole('dirigeant');
    User::factory()->create(['email' => 'taken@y.fr']);

    expect(fn () => app(UserInvitationService::class)->invite(
        data: ['first_name' => 'A', 'last_name' => 'B', 'email' => 'taken@y.fr', 'type' => UserType::Intervenant],
        structure: $dirigeant->structure,
        invitedBy: $dirigeant,
    ))->toThrow(QueryException::class);

    // No partial state — count should still be just $dirigeant + $taken.
    expect(User::where('email', 'taken@y.fr')->count())->toBe(1);
});

it('deactivate marks user inactive AND revokes Sanctum tokens', function (): void {
    Notification::fake();
    $dirigeant = actingAsRole('dirigeant');
    $target = User::factory()->forStructure($dirigeant->structure)->create();
    $target->createToken('mobile');

    expect($target->tokens()->count())->toBe(1);

    app(UserInvitationService::class)->deactivate($target);

    expect($target->fresh()->status)->toBe('inactive');
    expect($target->tokens()->count())->toBe(0);
});

it('reactivate restores active status', function (): void {
    Notification::fake();
    $target = User::factory()->create(['status' => 'inactive']);

    app(UserInvitationService::class)->reactivate($target);

    expect($target->fresh()->status)->toBe('active');
});
