<?php

declare(strict_types=1);

use App\Enums\QvctCampaignStatus;
use App\Models\QvctCampaign;
use App\Models\QvctResponse;
use App\Models\QvctWeakSignal;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

// ── Campaign discovery ─────────────────────────────────────────────────────

it('lists open campaigns by default for an authenticated intervenant', function (): void {
    $intervenant = actingAsApiRole('intervenant');

    QvctCampaign::factory()->forStructure($intervenant->structure)->create([
        'status' => QvctCampaignStatus::Active->value,
    ]);
    QvctCampaign::factory()->forStructure($intervenant->structure)->closed()->create();

    $response = $this->getJson('/api/v1/qvct/campaigns');
    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(1);
});

it('returns all campaigns when status=all', function (): void {
    $intervenant = actingAsApiRole('intervenant');

    QvctCampaign::factory()->forStructure($intervenant->structure)->create([
        'status' => QvctCampaignStatus::Active->value,
    ]);
    QvctCampaign::factory()->forStructure($intervenant->structure)->closed()->create();

    $response = $this->getJson('/api/v1/qvct/campaigns?status=all');
    expect($response->json('data'))->toHaveCount(2);
});

it('cannot read a foreign-tenant campaign', function (): void {
    actingAsApiRole('intervenant');
    $foreign = QvctCampaign::factory()->forStructure(Structure::factory()->create())->create();

    $this->getJson("/api/v1/qvct/campaigns/{$foreign->id}")->assertNotFound();
});

// ── Anonymous response submission (HTTP fallback for sync op) ───────────────

it('intervenant can submit a response — server never echoes response id', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $campaign = QvctCampaign::factory()->forStructure($intervenant->structure)->create();

    $response = $this->postJson("/api/v1/qvct/campaigns/{$campaign->id}/responses", [
        'answers' => ['morale' => 4, 'charge' => 3],
        'team_tag' => 'team_paris',
    ]);

    $response->assertCreated();
    expect($response->json('campaign_id'))->toBe($campaign->id);
    expect($response->json())->not->toHaveKey('id'); // anonymity: no individual response id
    expect(QvctResponse::where('campaign_id', $campaign->id)->count())->toBe(1);
});

it('rejects submission to a closed campaign with 409', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $campaign = QvctCampaign::factory()->forStructure($intervenant->structure)->closed()->create();

    $this->postJson("/api/v1/qvct/campaigns/{$campaign->id}/responses", [
        'answers' => ['morale' => 3],
    ])->assertStatus(409);
});

it('rejects submission with empty answers at validation', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $campaign = QvctCampaign::factory()->forStructure($intervenant->structure)->create();

    $this->postJson("/api/v1/qvct/campaigns/{$campaign->id}/responses", [
        'answers' => [],
    ])->assertStatus(422);
});

// ── RH weak-signal triage ──────────────────────────────────────────────────

it('rh can list outstanding weak signals only by default', function (): void {
    $rh = actingAsApiRole('rh');
    $campaign = QvctCampaign::factory()->forStructure($rh->structure)->create();

    QvctWeakSignal::factory()->forCampaign($campaign)->count(2)->create();
    QvctWeakSignal::factory()->forCampaign($campaign)->create([
        'acknowledged_by' => $rh->id,
        'acknowledged_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/qvct/weak-signals');
    $response->assertSuccessful();
    expect($response->json('data.data'))->toHaveCount(2); // outstanding only
});

it('rh can acknowledge a weak signal', function (): void {
    $rh = actingAsApiRole('rh');
    $campaign = QvctCampaign::factory()->forStructure($rh->structure)->create();
    $signal = QvctWeakSignal::factory()->forCampaign($campaign)->create();

    $response = $this->postJson("/api/v1/qvct/weak-signals/{$signal->id}/acknowledge");

    $response->assertSuccessful();
    expect($signal->fresh()->acknowledged_at)->not->toBeNull();
    expect($signal->fresh()->acknowledged_by)->toBe($rh->id);
});

it('intervenant cannot list weak signals', function (): void {
    actingAsApiRole('intervenant');

    $this->getJson('/api/v1/qvct/weak-signals')->assertForbidden();
});

it('intervenant cannot acknowledge a weak signal', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $campaign = QvctCampaign::factory()->forStructure($intervenant->structure)->create();
    $signal = QvctWeakSignal::factory()->forCampaign($campaign)->create();

    $this->postJson("/api/v1/qvct/weak-signals/{$signal->id}/acknowledge")->assertForbidden();
});
