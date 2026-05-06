<?php

declare(strict_types=1);

use App\Enums\UserType;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Verifies the Inertia shared `auth.abilities` map drives every persona's
 * UI visibility. Pages and action buttons are hidden purely by reading
 * this map on the React side, so the contract here MUST stay in sync with
 * the agreed RBAC matrix.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function loginAbilitiesPersona(string $role): User
{
    $structure = Structure::factory()->create();

    $user = User::factory()
        ->forStructure($structure)
        ->state([
            'type' => $role,
            'email_verified_at' => now(),
        ])
        ->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());
    $user->assignRole($role);

    app()->instance('current_structure', $structure);
    test()->actingAs($user);

    return $user;
}

it('shares auth.abilities on the dashboard for an intervenant', function () {
    loginAbilitiesPersona(UserType::Intervenant->value);

    $response = $this->get('/dashboard');

    $response->assertSuccessful();
    $abilities = $response->viewData('page')['props']['auth']['abilities'];

    expect($abilities['interventions.view'])->toBeTrue()
        ->and($abilities['incidents.create'])->toBeTrue()
        ->and($abilities['beneficiaries.view'])->toBeTrue()
        ->and($abilities['interventions.create'])->toBeFalse()
        ->and($abilities['audits.view'])->toBeFalse()
        ->and($abilities['users.manage'])->toBeFalse();
});

it('shares auth.abilities on the dashboard for a coordinateur', function () {
    loginAbilitiesPersona(UserType::Coordinateur->value);

    $response = $this->get('/dashboard');

    $response->assertSuccessful();
    $abilities = $response->viewData('page')['props']['auth']['abilities'];

    expect($abilities['interventions.create'])->toBeTrue()
        ->and($abilities['beneficiaries.create'])->toBeTrue()
        ->and($abilities['incidents.analyze'])->toBeTrue()
        ->and($abilities['audits.manage'])->toBeFalse()
        ->and($abilities['plans_amelioration.manage'])->toBeFalse()
        ->and($abilities['users.manage'])->toBeFalse();
});

it('shares auth.abilities on the dashboard for a dirigeant', function () {
    loginAbilitiesPersona(UserType::Dirigeant->value);

    $response = $this->get('/dashboard');

    $response->assertSuccessful();
    $abilities = $response->viewData('page')['props']['auth']['abilities'];

    expect($abilities['interventions.create'])->toBeTrue()
        ->and($abilities['audits.manage'])->toBeTrue()
        ->and($abilities['plans_amelioration.manage'])->toBeTrue()
        ->and($abilities['indicateurs.view'])->toBeTrue()
        ->and($abilities['users.manage'])->toBeTrue()
        ->and($abilities['admin.structures'])->toBeFalse();
});

it('shares auth.abilities on the dashboard for a referent_qualite', function () {
    loginAbilitiesPersona(UserType::ReferentQualite->value);

    $response = $this->get('/dashboard');

    $response->assertSuccessful();
    $abilities = $response->viewData('page')['props']['auth']['abilities'];

    expect($abilities['audits.manage'])->toBeTrue()
        ->and($abilities['plans_amelioration.manage'])->toBeTrue()
        ->and($abilities['incidents.analyze'])->toBeTrue()
        ->and($abilities['incidents.create'])->toBeFalse()
        ->and($abilities['interventions.create'])->toBeFalse()
        ->and($abilities['beneficiaries.create'])->toBeFalse()
        ->and($abilities['users.manage'])->toBeFalse();
});

it('shares auth.abilities on the dashboard for an rh', function () {
    loginAbilitiesPersona(UserType::Rh->value);

    $response = $this->get('/dashboard');

    $response->assertSuccessful();
    $abilities = $response->viewData('page')['props']['auth']['abilities'];

    expect($abilities['qvct.manage'])->toBeTrue()
        ->and($abilities['formations.manage'])->toBeTrue()
        ->and($abilities['users.manage'])->toBeTrue()
        ->and($abilities['interventions.view'])->toBeFalse()
        ->and($abilities['beneficiaries.view'])->toBeFalse()
        ->and($abilities['audits.view'])->toBeFalse();
});

it('shares no abilities for unauthenticated requests', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $abilities = $response->viewData('page')['props']['auth']['abilities'] ?? null;

    // Guests get the empty map (every key false).
    expect($abilities)->toBeArray();
    foreach ($abilities as $value) {
        expect($value)->toBeFalse();
    }
});
