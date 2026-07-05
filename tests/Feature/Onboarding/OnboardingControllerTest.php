<?php

declare(strict_types=1);

use App\Models\Beneficiary;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('renders onboarding with a freshly-empty snapshot for the first user of a structure', function (): void {
    actingAsRole('dirigeant');

    $response = $this->get('/dashboard/onboarding');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];
    expect($props)->toHaveKeys(['completed', 'counts', 'mfa_enrolled', 'all_critical_done']);
    expect($props['mfa_enrolled'])->toBeFalse();
    expect($props['all_critical_done'])->toBeFalse();
    expect($props['completed'])->not->toContain('team');
    expect($props['completed'])->not->toContain('beneficiary');
});

it('flags team step as completed once a structure has two or more users', function (): void {
    $user = actingAsRole('dirigeant');
    User::factory()->forStructure($user->structure)->create();

    $response = $this->get('/dashboard/onboarding');

    $props = $response->viewData('page')['props'];
    expect($props['completed'])->toContain('team');
    expect($props['counts']['users'])->toBe(2);
});

it('flags beneficiary step as completed once at least one beneficiary exists', function (): void {
    $user = actingAsRole('dirigeant');
    Beneficiary::factory()->create(['structure_id' => $user->structure->id]);

    $response = $this->get('/dashboard/onboarding');

    $props = $response->viewData('page')['props'];
    expect($props['completed'])->toContain('beneficiary');
    expect($props['counts']['beneficiaries'])->toBe(1);
});

it('flags MFA step as completed when the user has confirmed two-factor', function (): void {
    $user = actingAsRole('dirigeant');
    $user->forceFill(['two_factor_confirmed_at' => now()])->save();

    $response = $this->get('/dashboard/onboarding');

    $props = $response->viewData('page')['props'];
    expect($props['completed'])->toContain('mfa');
    expect($props['mfa_enrolled'])->toBeTrue();
});

it('marks all_critical_done when team, mfa and beneficiary are satisfied', function (): void {
    $user = actingAsRole('dirigeant');
    $user->forceFill(['two_factor_confirmed_at' => now()])->save();
    User::factory()->forStructure($user->structure)->create();
    Beneficiary::factory()->create(['structure_id' => $user->structure->id]);

    $response = $this->get('/dashboard/onboarding');

    $props = $response->viewData('page')['props'];
    expect($props['all_critical_done'])->toBeTrue();
    expect($props['completed'])->toEqualCanonicalizing(['team', 'mfa', 'beneficiary']);
});

it('does not leak the counts across structures', function (): void {
    $userA = actingAsRole('dirigeant');
    User::factory()->forStructure($userA->structure)->create();

    // Out-of-tenant noise
    $otherStructure = Structure::factory()->create();
    User::factory()->forStructure($otherStructure)->count(5)->create();
    Beneficiary::factory()->count(3)->create(['structure_id' => $otherStructure->id]);

    $response = $this->get('/dashboard/onboarding');

    $props = $response->viewData('page')['props'];
    expect($props['counts']['users'])->toBe(2);
    expect($props['counts']['beneficiaries'])->toBe(0);
});

it('serves the public registre des traitements page', function (): void {
    $this->get('/registre-traitements')->assertSuccessful();
});
