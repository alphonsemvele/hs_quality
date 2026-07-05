<?php

declare(strict_types=1);

use App\Enums\AccountDeletionStatus;
use App\Jobs\Gdpr\ProcessAccountDeletionRequestsJob;
use App\Models\AccountDeletionRequest;
use App\Models\Structure;
use App\Models\User;
use App\Services\Gdpr\AnonymizeUserAccountService;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('creates a pending deletion request when the user confirms', function (): void {
    $user = actingAsRole('coordinateur');

    $this->post('/dashboard/profile/gdpr/delete-account', ['confirm' => true])
        ->assertRedirect()
        ->assertSessionHas('success');

    $request = AccountDeletionRequest::query()->where('user_id', $user->id)->first();
    expect($request)->not->toBeNull();
    expect($request->status)->toBe(AccountDeletionStatus::Pending);
    expect($request->effective_at->isAfter(now()->addDays(29)))->toBeTrue();
});

it('refuses to create a duplicate pending request', function (): void {
    $user = actingAsRole('coordinateur');

    $this->post('/dashboard/profile/gdpr/delete-account', ['confirm' => true]);
    $this->post('/dashboard/profile/gdpr/delete-account', ['confirm' => true])
        ->assertSessionHas('info');

    expect(AccountDeletionRequest::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('lets the user cancel a pending request within the 30-day window', function (): void {
    $user = actingAsRole('coordinateur');

    $this->post('/dashboard/profile/gdpr/delete-account', ['confirm' => true]);

    $this->post('/dashboard/profile/gdpr/delete-account/cancel')
        ->assertRedirect()
        ->assertSessionHas('success');

    $request = AccountDeletionRequest::query()->where('user_id', $user->id)->first();
    expect($request->status)->toBe(AccountDeletionStatus::Cancelled);
});

it('exposes the deletion status on the GDPR page', function (): void {
    $user = actingAsRole('coordinateur');

    $this->post('/dashboard/profile/gdpr/delete-account', ['confirm' => true]);

    $response = $this->get('/dashboard/profile/gdpr');

    $props = $response->viewData('page')['props'];
    expect($props['deletion'])->not->toBeNull();
    expect($props['deletion']['status'])->toBe(AccountDeletionStatus::Pending->value);
    expect($props['deletion']['can_cancel'])->toBeTrue();
});

it('processes a due deletion request via the scheduled job', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);
    $user = User::factory()->forStructure($structure)->create([
        'first_name' => 'Bob',
        'last_name' => 'Test',
    ]);

    AccountDeletionRequest::factory()->due()->create([
        'structure_id' => $structure->id,
        'user_id' => $user->id,
    ]);

    (new ProcessAccountDeletionRequestsJob)->handle(
        app(AnonymizeUserAccountService::class),
    );

    $request = AccountDeletionRequest::query()->withoutGlobalScopes()->where('user_id', $user->id)->first();
    expect($request->status)->toBe(AccountDeletionStatus::Processed);
    expect($request->processed_at)->not->toBeNull();

    $refreshed = User::withTrashed()->find($user->id);
    expect($refreshed->first_name)->toBe('Compte');
    expect($refreshed->erased_at)->not->toBeNull();
});

it('skips a deletion request that is not yet due', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);
    $user = User::factory()->forStructure($structure)->create();

    AccountDeletionRequest::factory()->create([
        'structure_id' => $structure->id,
        'user_id' => $user->id,
        'effective_at' => now()->addDays(10),
    ]);

    (new ProcessAccountDeletionRequestsJob)->handle(
        app(AnonymizeUserAccountService::class),
    );

    $request = AccountDeletionRequest::query()->withoutGlobalScopes()->where('user_id', $user->id)->first();
    expect($request->status)->toBe(AccountDeletionStatus::Pending);
    expect($user->fresh()->first_name)->not->toBe('Compte');
});

it('skips a cancelled deletion request even when its effective date is past', function (): void {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);
    $user = User::factory()->forStructure($structure)->create(['first_name' => 'Original']);

    AccountDeletionRequest::factory()->due()->cancelled()->create([
        'structure_id' => $structure->id,
        'user_id' => $user->id,
    ]);

    (new ProcessAccountDeletionRequestsJob)->handle(
        app(AnonymizeUserAccountService::class),
    );

    expect($user->fresh()->first_name)->toBe('Original');
});
