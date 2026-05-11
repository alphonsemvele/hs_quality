<?php

declare(strict_types=1);

use App\Enums\QvctExchangeStatus;
use App\Models\QvctExchangeRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('intervenant can create an exchange request via API', function (): void {
    $intervenant = actingAsApiRole('intervenant');

    $response = $this->postJson('/api/v1/qvct/exchange-requests', [
        'addressee_role' => 'rh',
        'message' => 'Besoin d\'un échange.',
    ]);

    $response->assertCreated();
    expect(QvctExchangeRequest::where('requester_id', $intervenant->id)->count())->toBe(1);
});

it('intervenant sees only their own outgoing requests on /mine', function (): void {
    $intervenant = actingAsApiRole('intervenant');

    QvctExchangeRequest::factory()->fromUser($intervenant)->count(2)->create();
    $other = User::factory()->forStructure($intervenant->structure)->intervenant()->create();
    QvctExchangeRequest::factory()->fromUser($other)->count(3)->create();

    $response = $this->getJson('/api/v1/qvct/exchange-requests/mine');
    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

it('rh sees pending RH-addressed requests on /incoming', function (): void {
    $rh = actingAsApiRole('rh');
    $intervenant = User::factory()->forStructure($rh->structure)->intervenant()->create();

    QvctExchangeRequest::factory()->fromUser($intervenant)->toRh()->count(2)->create();
    QvctExchangeRequest::factory()->fromUser($intervenant)->toManager()->count(3)->create();
    QvctExchangeRequest::factory()->fromUser($intervenant)->toRh()
        ->create(['status' => QvctExchangeStatus::Closed->value, 'closed_at' => now()]);

    $response = $this->getJson('/api/v1/qvct/exchange-requests/incoming');
    $response->assertSuccessful();
    // 2 RH-addressed pending + 0 closed = 2 (manager-addressed not visible to RH)
    expect($response->json('data'))->toHaveCount(2);
});

it('rh can accept then schedule an incoming request', function (): void {
    $rh = actingAsApiRole('rh');
    $intervenant = User::factory()->forStructure($rh->structure)->intervenant()->create();
    $req = QvctExchangeRequest::factory()->fromUser($intervenant)->toRh()->create();

    $accept = $this->postJson("/api/v1/qvct/exchange-requests/{$req->id}/accept");
    $accept->assertSuccessful();
    expect($accept->json('status'))->toBe('accepted');

    $schedule = $this->postJson("/api/v1/qvct/exchange-requests/{$req->id}/schedule", [
        'scheduled_at' => now()->addDay()->toIso8601String(),
    ]);
    $schedule->assertSuccessful();
    expect($schedule->json('status'))->toBe('scheduled');
});

it('rejects scheduling without acceptance with 409', function (): void {
    $rh = actingAsApiRole('rh');
    $intervenant = User::factory()->forStructure($rh->structure)->intervenant()->create();
    $req = QvctExchangeRequest::factory()->fromUser($intervenant)->toRh()->create();

    $this->postJson("/api/v1/qvct/exchange-requests/{$req->id}/schedule", [
        'scheduled_at' => now()->addDay()->toIso8601String(),
    ])->assertStatus(409);
});

it('foreign-tenant request returns 404 from /accept', function (): void {
    actingAsApiRole('rh');
    $foreignReq = QvctExchangeRequest::factory()->create();

    $this->postJson("/api/v1/qvct/exchange-requests/{$foreignReq->id}/accept")
        ->assertNotFound();
});

it('rejects creation with invalid addressee_role at validation', function (): void {
    actingAsApiRole('intervenant');

    $this->postJson('/api/v1/qvct/exchange-requests', [
        'addressee_role' => 'admin',
    ])->assertStatus(422);
});

// ── Sync op ──────────────────────────────────────────────────────────────────

it('creates an exchange request via the sync batch envelope', function (): void {
    $intervenant = actingAsApiRole('intervenant');

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'qvct.request_exchange',
            'payload' => [
                'addressee_role' => 'rh',
                'message' => 'Need to talk after this difficult tour.',
            ],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('success');
    expect(QvctExchangeRequest::where('requester_id', $intervenant->id)->count())->toBe(1);
});
