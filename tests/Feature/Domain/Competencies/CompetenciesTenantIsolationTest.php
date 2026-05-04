<?php

declare(strict_types=1);

use App\Models\Certification;
use App\Models\Habilitation;
use App\Models\Structure;
use App\Models\User;

/**
 * Cross-tenant leak tests for the M5.1 habilitations and M5.2
 * certifications tables. Spec: PHASE2_PROGRESS.md M5.7 partial.
 */
beforeEach(function (): void {
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

it('only returns habilitations from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $userA = User::factory()->forStructure($a)->create();
    $userB = User::factory()->forStructure($b)->create();

    Habilitation::factory()->forUser($userA)->count(2)->create();
    Habilitation::factory()->forUser($userB)->count(3)->create();

    app()->instance('current_structure', $a);
    expect(Habilitation::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(Habilitation::count())->toBe(3);
});

it('only returns certifications from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    $userA = User::factory()->forStructure($a)->create();
    $userB = User::factory()->forStructure($b)->create();

    Certification::factory()->forUser($userA)->count(1)->create();
    Certification::factory()->forUser($userB)->count(4)->create();

    app()->instance('current_structure', $a);
    expect(Certification::count())->toBe(1);

    app()->instance('current_structure', $b);
    expect(Certification::count())->toBe(4);
});
