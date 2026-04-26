<?php

declare(strict_types=1);

use App\Enums\StructureStatus;
use App\Enums\StructureTier;
use App\Enums\StructureType;
use App\Enums\UserType;
use App\Models\Structure;
use App\Models\User;
use App\Services\StructureService;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('atomically creates a Structure + initial Dirigeant + role assignment', function (): void {
    $service = app(StructureService::class);

    $result = $service->provision(
        structureData: [
            'code' => 'TEST-AAA',
            'name' => 'Test SAAD',
            'type' => StructureType::SAAD,
            'tier' => StructureTier::Pro,
        ],
        dirigeantData: [
            'first_name' => 'Marie',
            'last_name' => 'Durand',
            'email' => 'm.durand@test.fr',
        ],
    );

    $structure = $result['structure'];
    $dirigeant = $result['dirigeant'];

    expect($structure)->toBeInstanceOf(Structure::class);
    expect($structure->code)->toBe('TEST-AAA');
    expect($structure->type)->toBe(StructureType::SAAD);
    expect($structure->tier)->toBe(StructureTier::Pro);
    expect($structure->status)->toBe(StructureStatus::Active);

    expect($dirigeant->structure_id)->toBe($structure->id);
    expect($dirigeant->email)->toBe('m.durand@test.fr');
    expect($dirigeant->type)->toBe(UserType::Dirigeant);
    expect($dirigeant->email_verified_at)->toBeNull();

    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->id);
    expect($dirigeant->fresh()->hasRole('dirigeant'))->toBeTrue();
});

it('uppercases the structure code', function (): void {
    $result = app(StructureService::class)->provision(
        structureData: ['code' => 'lowercase-code', 'name' => 'X', 'type' => StructureType::SAAD],
        dirigeantData: ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.fr'],
    );

    expect($result['structure']->code)->toBe('LOWERCASE-CODE');
});

it('lowercases the dirigeant email', function (): void {
    $result = app(StructureService::class)->provision(
        structureData: ['code' => 'CODE2', 'name' => 'X', 'type' => StructureType::SAAD],
        dirigeantData: ['first_name' => 'A', 'last_name' => 'B', 'email' => 'MIXED@CASE.fr'],
    );

    expect($result['dirigeant']->email)->toBe('mixed@case.fr');
});

it('rolls back if Dirigeant creation fails (atomic)', function (): void {
    User::factory()->create(['email' => 'taken@x.fr']);

    expect(fn () => app(StructureService::class)->provision(
        structureData: ['code' => 'WILL-ROLLBACK', 'name' => 'X', 'type' => StructureType::SAAD],
        dirigeantData: ['first_name' => 'A', 'last_name' => 'B', 'email' => 'taken@x.fr'],
    ))->toThrow(QueryException::class);

    expect(Structure::where('code', 'WILL-ROLLBACK')->exists())->toBeFalse();
});

it('suspends and reactivates a structure', function (): void {
    $structure = Structure::factory()->create();
    $service = app(StructureService::class);

    expect($service->suspend($structure)->status)->toBe(StructureStatus::Suspended);
    expect($service->reactivate($structure)->status)->toBe(StructureStatus::Active);
});
