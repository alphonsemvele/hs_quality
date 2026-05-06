<?php

declare(strict_types=1);

use App\Enums\UserType;
use App\Models\User;
use App\Support\UserAbilities;

/**
 * Pure-PHP test of the role → ability matrix shared with Inertia.
 *
 * No database; we hydrate User instances in-memory and assert the
 * matrix matches what the React layer relies on to hide buttons.
 */
function makeAbilitiesUser(?UserType $type, bool $platformAdmin = false): User
{
    $user = new User;
    $user->forceFill([
        'id' => 1,
        'type' => $type?->value,
        'is_platform_admin' => $platformAdmin,
    ]);
    $user->setRawAttributes($user->getAttributes(), true);

    return $user;
}

it('returns an empty map for a guest', function () {
    $abilities = UserAbilities::for(null);

    expect($abilities)->toBeArray();
    foreach ($abilities as $value) {
        expect($value)->toBeFalse();
    }
});

it('grants admin.structures only to platform admins', function () {
    $user = makeAbilitiesUser(null, platformAdmin: true);

    $abilities = UserAbilities::for($user);

    expect($abilities['admin.structures'])->toBeTrue()
        ->and($abilities['interventions.view'])->toBeFalse()
        ->and($abilities['users.manage'])->toBeFalse();
});

it('matches the agreed matrix for a dirigeant', function () {
    $abilities = UserAbilities::for(makeAbilitiesUser(UserType::Dirigeant));

    expect($abilities['interventions.view'])->toBeTrue()
        ->and($abilities['interventions.create'])->toBeTrue()
        ->and($abilities['incidents.create'])->toBeTrue()
        ->and($abilities['incidents.analyze'])->toBeTrue()
        ->and($abilities['beneficiaries.create'])->toBeTrue()
        ->and($abilities['audits.manage'])->toBeTrue()
        ->and($abilities['plans_amelioration.manage'])->toBeTrue()
        ->and($abilities['indicateurs.view'])->toBeTrue()
        ->and($abilities['qvct.manage'])->toBeTrue()
        ->and($abilities['formations.manage'])->toBeTrue()
        ->and($abilities['users.manage'])->toBeTrue()
        ->and($abilities['admin.structures'])->toBeFalse();
});

it('matches the agreed matrix for a coordinateur', function () {
    $abilities = UserAbilities::for(makeAbilitiesUser(UserType::Coordinateur));

    expect($abilities['interventions.create'])->toBeTrue()
        ->and($abilities['incidents.analyze'])->toBeTrue()
        ->and($abilities['beneficiaries.create'])->toBeTrue()
        ->and($abilities['qvct.view'])->toBeTrue()
        // Quality module is referent_qualite / dirigeant only.
        ->and($abilities['audits.view'])->toBeFalse()
        ->and($abilities['audits.manage'])->toBeFalse()
        ->and($abilities['plans_amelioration.view'])->toBeFalse()
        ->and($abilities['indicateurs.view'])->toBeFalse()
        // RH-only.
        ->and($abilities['users.manage'])->toBeFalse()
        ->and($abilities['formations.manage'])->toBeFalse();
});

it('matches the agreed matrix for a referent_qualite', function () {
    $abilities = UserAbilities::for(makeAbilitiesUser(UserType::ReferentQualite));

    expect($abilities['audits.manage'])->toBeTrue()
        ->and($abilities['plans_amelioration.manage'])->toBeTrue()
        ->and($abilities['indicateurs.view'])->toBeTrue()
        ->and($abilities['incidents.analyze'])->toBeTrue()
        ->and($abilities['qvct.view'])->toBeTrue()
        // Cannot plan interventions or create beneficiaries.
        ->and($abilities['interventions.create'])->toBeFalse()
        ->and($abilities['beneficiaries.create'])->toBeFalse()
        // Quality lead analyses incidents but does NOT declare them
        // (declaration comes from intervenants / coordinateurs).
        ->and($abilities['incidents.create'])->toBeFalse()
        // Not user/formation manager.
        ->and($abilities['users.manage'])->toBeFalse()
        ->and($abilities['formations.manage'])->toBeFalse();
});

it('matches the agreed matrix for an intervenant', function () {
    $abilities = UserAbilities::for(makeAbilitiesUser(UserType::Intervenant));

    expect($abilities['interventions.view'])->toBeTrue()
        ->and($abilities['interventions.update'])->toBeTrue()
        ->and($abilities['incidents.create'])->toBeTrue()
        ->and($abilities['beneficiaries.view'])->toBeTrue()
        // Field worker cannot plan / create / manage.
        ->and($abilities['interventions.create'])->toBeFalse()
        ->and($abilities['interventions.delete'])->toBeFalse()
        ->and($abilities['incidents.analyze'])->toBeFalse()
        ->and($abilities['beneficiaries.create'])->toBeFalse()
        ->and($abilities['audits.view'])->toBeFalse()
        ->and($abilities['audits.manage'])->toBeFalse()
        ->and($abilities['users.manage'])->toBeFalse()
        ->and($abilities['qvct.view'])->toBeFalse();
});

it('matches the agreed matrix for an rh', function () {
    $abilities = UserAbilities::for(makeAbilitiesUser(UserType::Rh));

    expect($abilities['qvct.manage'])->toBeTrue()
        ->and($abilities['formations.manage'])->toBeTrue()
        ->and($abilities['users.manage'])->toBeTrue()
        ->and($abilities['communication.post'])->toBeTrue()
        // No operational / quality access.
        ->and($abilities['interventions.view'])->toBeFalse()
        ->and($abilities['incidents.view'])->toBeFalse()
        ->and($abilities['beneficiaries.view'])->toBeFalse()
        ->and($abilities['audits.view'])->toBeFalse()
        ->and($abilities['plans_amelioration.view'])->toBeFalse()
        ->and($abilities['indicateurs.view'])->toBeFalse();
});

it('exposes every key listed in keys() for every persona', function () {
    foreach (UserType::cases() as $type) {
        $abilities = UserAbilities::for(makeAbilitiesUser($type));

        foreach (UserAbilities::keys() as $key) {
            expect($abilities)->toHaveKey($key)
                ->and($abilities[$key])->toBeBool();
        }
    }
});
