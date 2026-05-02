<?php

declare(strict_types=1);

use App\Models\QvctCampaign;
use App\Models\QvctResponse;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('submits a QVCT response via the sync batch envelope', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $campaign = QvctCampaign::factory()->forStructure($intervenant->structure)->create();

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'qvct.submit_response',
            'resource_id' => $campaign->id,
            'payload' => [
                'answers' => [
                    'morale' => 4,
                    'charge' => 3,
                    'relations' => 5,
                    'isolement' => 4,
                ],
                'team_tag' => 'team_paris',
            ],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('success');
    expect($response->json('results.0.server_state.campaign_id'))->toBe($campaign->id);
    expect($response->json('results.0.server_state'))->not->toHaveKey('id'); // anonymity: no response id echoed

    expect(QvctResponse::query()
        ->where('campaign_id', $campaign->id)
        ->where('team_tag', 'team_paris')
        ->count())->toBe(1);
});

it('rejects a QVCT submission to a closed campaign as conflict', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $campaign = QvctCampaign::factory()
        ->forStructure($intervenant->structure)
        ->closed()
        ->create();

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'qvct.submit_response',
            'resource_id' => $campaign->id,
            'payload' => ['answers' => ['morale' => 3]],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('conflict');
    expect(QvctResponse::query()->where('campaign_id', $campaign->id)->count())->toBe(0);
});

it('rejects a QVCT submission to a foreign-tenant campaign as rejected (404)', function (): void {
    actingAsApiRole('intervenant');
    $foreignStructure = Structure::factory()->create();
    $foreignCampaign = QvctCampaign::factory()->forStructure($foreignStructure)->create();

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'qvct.submit_response',
            'resource_id' => $foreignCampaign->id,
            'payload' => ['answers' => ['morale' => 3]],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('rejected');
    expect(QvctResponse::query()->where('campaign_id', $foreignCampaign->id)->count())->toBe(0);
});

it('rejects a QVCT submission with missing campaign id at validation', function (): void {
    actingAsApiRole('intervenant');

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'qvct.submit_response',
            // resource_id missing
            'payload' => ['answers' => ['morale' => 3]],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('error');
});
