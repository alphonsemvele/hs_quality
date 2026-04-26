<?php

declare(strict_types=1);

use App\Models\Incident;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Block A / #56 — POST /api/v1/incidents accepts an optional client-assigned
 * UUID for offline-first mobile. Replay of the same id is a no-op.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('accepts a client-assigned UUID and stores it as the incident id', function (): void {
    actingAsApiRole('coordinateur');
    $clientId = (string) Str::uuid();

    $response = $this->postJson('/api/v1/incidents', [
        'id' => $clientId,
        'occurred_at' => now()->subMinutes(15)->toIso8601String(),
        'categorie' => 'chute',
        'description' => 'Bénéficiaire chute en se levant.',
    ]);

    $response->assertStatus(201);
    expect($response->json('id'))->toBe($clientId);
    expect(Incident::find($clientId))->not->toBeNull();
});

it('falls back to server-generated UUID when no id provided', function (): void {
    actingAsApiRole('coordinateur');

    $response = $this->postJson('/api/v1/incidents', [
        'occurred_at' => now()->subMinutes(15)->toIso8601String(),
        'categorie' => 'chute',
        'description' => 'Test',
    ]);

    $response->assertStatus(201);
    expect($response->json('id'))->toBeString();
    expect(strlen($response->json('id')))->toBe(36);
});

it('replays the same UUID idempotently without creating a duplicate', function (): void {
    actingAsApiRole('coordinateur');
    $clientId = (string) Str::uuid();

    $payload = [
        'id' => $clientId,
        'occurred_at' => now()->subMinutes(10)->toIso8601String(),
        'categorie' => 'chute',
        'description' => 'Original',
    ];

    $r1 = $this->postJson('/api/v1/incidents', $payload);
    $r1->assertStatus(201);

    // Same id, even with DIFFERENT body — replay returns the original.
    $r2 = $this->postJson('/api/v1/incidents', [...$payload, 'description' => 'Different body, same id']);
    $r2->assertStatus(201);

    expect($r1->json('id'))->toBe($r2->json('id'))->toBe($clientId);
    expect(Incident::count())->toBe(1);
});

it('rejects a non-UUID id at validation', function (): void {
    actingAsApiRole('coordinateur');

    $this->postJson('/api/v1/incidents', [
        'id' => 'not-a-uuid',
        'occurred_at' => now()->toIso8601String(),
        'categorie' => 'chute',
        'description' => 'X',
    ])->assertStatus(422);
});

it('rejects a client id that collides with a foreign-tenant incident (422)', function (): void {
    // Arrange: persist an incident with id X under structure A. Note we
    // can't use `Incident::create(['id' => ..., ...])` because `id` isn't
    // mass-assignable — assign it explicitly post-construct.
    $structureA = Structure::factory()->create();
    $userA = User::factory()->forStructure($structureA)->coordinateur()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($structureA->id);
    $userA->assignRole('coordinateur');
    app()->instance('current_structure', $structureA);

    $foreignId = (string) Str::uuid();
    $foreign = new Incident([
        'structure_id' => $structureA->id,
        'declared_by' => $userA->id,
        'occurred_at' => now()->subHour(),
        'categorie' => 'chute',
        'description' => 'Foreign tenant incident',
        'gravite' => 'mineur',
        'statut' => 'declare',
    ]);
    $foreign->id = $foreignId;
    $foreign->save();

    // Act: a coordinateur in a fresh tenant B POSTs with the same id.
    app()->forgetInstance('current_structure');
    actingAsApiRole('coordinateur');

    $response = $this->postJson('/api/v1/incidents', [
        'id' => $foreignId,
        'occurred_at' => now()->subMinutes(10)->toIso8601String(),
        'categorie' => 'chute',
        'description' => 'Tenant B trying same id',
    ]);

    // Assert: 422 — service rejects with `incident_id_already_in_use`
    // before the DB-level PK violation. Original foreign incident is
    // untouched, no new row created.
    $response->assertStatus(422);
    expect(Incident::withoutGlobalScopes()
        ->where('id', $foreignId)
        ->first()
        ->description)->toBe('Foreign tenant incident');
    expect(Incident::withoutGlobalScopes()->count())->toBe(1);
});
