<?php

declare(strict_types=1);

use App\Enums\InterventionStatus;
use App\Models\Beneficiary;
use App\Models\Incident;
use App\Models\Intervention;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Str;

/**
 * #38 — POST /api/v1/sync/batch.
 *
 * The mobile app posts an array of queued offline operations. The server
 * replies 207 Multi-Status with per-op outcomes so the client can update
 * each local op independently (success, conflict, rejected, error).
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

function plannedIntervention(User $intervenant): Intervention
{
    $beneficiary = Beneficiary::factory()->forStructure($intervenant->structure)->create();

    return Intervention::factory()
        ->forStructure($intervenant->structure)
        ->state([
            'beneficiary_id' => $beneficiary->id,
            'intervenant_id' => $intervenant->id,
            'status' => InterventionStatus::Planned->value,
        ])
        ->create();
}

it('returns 207 with one success result for a check-in op', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $intervention = plannedIntervention($intervenant);

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'intervention.check_in',
            'resource_id' => $intervention->id,
            'payload' => ['latitude' => 48.85, 'longitude' => 2.35],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('count'))->toBe(1);
    expect($response->json('results.0.status'))->toBe('success');
    expect($response->json('results.0.server_state.status'))->toBe('in_progress');
    expect($intervention->fresh()->status->value)->toBe('in_progress');
});

it('isolates per-op failures — others continue when one is rejected', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $i1 = plannedIntervention($intervenant);

    // i2 is already completed — InterventionPolicy::update returns false for
    // terminal status, so cancel is REJECTED at the policy gate (not a 409
    // conflict from the service). Per-op result must still flow back so the
    // mobile client can reconcile.
    $i2 = plannedIntervention($intervenant);
    $i2->update(['status' => InterventionStatus::Completed->value, 'actual_end_at' => now()]);

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [
            [
                'client_op_id' => (string) Str::uuid(),
                'kind' => 'intervention.check_in',
                'resource_id' => $i1->id,
                'payload' => [],
            ],
            [
                'client_op_id' => (string) Str::uuid(),
                'kind' => 'intervention.cancel',
                'resource_id' => $i2->id,
                'payload' => ['reason' => 'No-show'],
            ],
        ],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('success');
    expect($response->json('results.1.status'))->toBe('rejected');
    expect($response->json('results.1.server_state.status'))->toBe('completed');
});

it('returns 409 conflict when the service rejects state (not the policy)', function (): void {
    // Different scenario: an intervention that is in_progress when the
    // mobile app tries a check_in. Policy::update would PASS (not terminal),
    // but InterventionService::checkIn throws 409 because the precondition
    // is "must be planned". This is the path that yields a `conflict` status.
    $intervenant = actingAsApiRole('intervenant');
    $i = plannedIntervention($intervenant);
    $i->update(['status' => InterventionStatus::InProgress->value, 'actual_start_at' => now()]);

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'intervention.check_in',
            'resource_id' => $i->id,
            'payload' => [],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('conflict');
    expect($response->json('results.0.server_state.status'))->toBe('in_progress');
});

it('rejects unknown operation kinds at validation', function (): void {
    actingAsApiRole('intervenant');

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'intervention.delete',
            'resource_id' => (string) Str::uuid(),
            'payload' => [],
        ]],
    ]);

    $response->assertStatus(422);
});

it('rejects batches over the size cap', function (): void {
    actingAsApiRole('intervenant');

    $ops = array_fill(0, 201, [
        'client_op_id' => (string) Str::uuid(),
        'kind' => 'intervention.check_in',
        'resource_id' => (string) Str::uuid(),
        'payload' => [],
    ]);

    $this->postJson('/api/v1/sync/batch', ['operations' => $ops])->assertStatus(422);
});

it('returns 404-equivalent (rejected) for an intervention from another tenant', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $foreignStructure = Structure::factory()->create();
    $foreignIntervenant = User::factory()->forStructure($foreignStructure)->intervenant()->create();
    $foreignIntervention = plannedIntervention($foreignIntervenant);

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'intervention.check_in',
            'resource_id' => $foreignIntervention->id,
            'payload' => [],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('rejected');
    expect($foreignIntervention->fresh()->status->value)->toBe('planned');
});

it('declares an incident via the batch (incident.create)', function (): void {
    $intervenant = actingAsApiRole('intervenant');

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'incident.create',
            'payload' => [
                'categorie' => 'chute',
                'description' => 'Beneficiary fell while transferring.',
                'occurred_at' => now()->subMinutes(15)->toIso8601String(),
                'avec_deces' => false,
                'avec_hospitalisation' => false,
                'avec_blessure_physique' => true,
            ],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('success');
    expect($response->json('results.0.server_state.statut'))->toBe('declare');
    expect(Incident::count())->toBe(1);
});

it('replays a single op when its client_op_id appears twice in one batch', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $intervention = plannedIntervention($intervenant);
    $opId = (string) Str::uuid();

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [
            [
                'client_op_id' => $opId,
                'kind' => 'intervention.check_in',
                'resource_id' => $intervention->id,
                'payload' => [],
            ],
            [
                'client_op_id' => $opId,
                'kind' => 'intervention.check_in',
                'resource_id' => $intervention->id,
                'payload' => [],
            ],
        ],
    ]);

    $response->assertStatus(207);
    expect($response->json('count'))->toBe(2);
    expect($response->json('results.0.status'))->toBe('success');
    expect($response->json('results.1.status'))->toBe('success');
    // Status only changed once — no double check-in.
    expect($intervention->fresh()->status->value)->toBe('in_progress');
});

it('requires authentication', function (): void {
    $this->postJson('/api/v1/sync/batch', ['operations' => []])
        ->assertStatus(401);
});

it('rejects an empty operations array at validation', function (): void {
    actingAsApiRole('intervenant');

    $this->postJson('/api/v1/sync/batch', ['operations' => []])
        ->assertStatus(422);
});
