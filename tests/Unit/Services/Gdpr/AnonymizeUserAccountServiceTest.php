<?php

declare(strict_types=1);

use App\Models\Structure;
use App\Models\User;
use App\Services\Gdpr\AnonymizeUserAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(AnonymizeUserAccountService::class);
});

it('scrambles user PII and stamps erased_at', function (): void {
    $structure = Structure::factory()->create();
    $user = User::factory()->forStructure($structure)->create([
        'first_name' => 'Alice',
        'last_name' => 'Durand',
        'email' => 'alice.durand@example.com',
        'phone' => '0612345678',
    ]);

    $this->service->anonymize($user);

    $refreshed = User::withTrashed()->find($user->id);
    expect($refreshed->first_name)->toBe('Compte');
    expect($refreshed->last_name)->toBe('effacé');
    expect($refreshed->email)->toContain('@erased.qualitedomicile.local');
    expect($refreshed->phone)->toBeNull();
    expect($refreshed->employee_number)->toBeNull();
    expect($refreshed->two_factor_secret)->toBeNull();
    expect($refreshed->status)->toBe('erased');
    expect($refreshed->erased_at)->not->toBeNull();
    expect($refreshed->trashed())->toBeTrue();
});

it('is idempotent on an already-erased user', function (): void {
    $user = User::factory()->forStructure(Structure::factory()->create())->create([
        'erased_at' => now()->subDay(),
        'first_name' => 'Already-Erased',
    ]);

    $result = $this->service->anonymize($user);

    expect($result->first_name)->toBe('Already-Erased');
});

it('revokes all Sanctum tokens belonging to the user', function (): void {
    $user = User::factory()->forStructure(Structure::factory()->create())->create();
    $user->createToken('mobile-app');
    $user->createToken('cli');

    expect($user->tokens()->count())->toBe(2);

    $this->service->anonymize($user);

    expect(User::withTrashed()->find($user->id)->tokens()->count())->toBe(0);
});
