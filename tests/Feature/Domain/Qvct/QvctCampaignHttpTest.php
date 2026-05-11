<?php

declare(strict_types=1);

use App\Enums\QvctCampaignStatus;
use App\Models\QvctCampaign;
use App\Models\QvctQuestionnaire;
use App\Models\QvctResponse;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('rh can launch a campaign from a questionnaire', function (): void {
    $rh = actingAsRole('rh');
    $q = QvctQuestionnaire::factory()->forStructure($rh->structure)->create();

    $this->post("/qvct/questionnaires/{$q->id}/campaigns", [
        'opens_at' => '2026-05-01',
        'closes_at' => '2026-05-15',
    ])->assertRedirect();

    expect(QvctCampaign::query()->where('questionnaire_id', $q->id)->count())->toBe(1);
});

it('intervenant cannot launch a campaign', function (): void {
    $intervenant = actingAsRole('intervenant');
    $q = QvctQuestionnaire::factory()->forStructure($intervenant->structure)->create();

    $this->post("/qvct/questionnaires/{$q->id}/campaigns", [
        'opens_at' => '2026-05-01',
        'closes_at' => '2026-05-15',
    ])->assertForbidden();

    expect(QvctCampaign::count())->toBe(0);
});

it('rejects launch with closes_at before opens_at', function (): void {
    $rh = actingAsRole('rh');
    $q = QvctQuestionnaire::factory()->forStructure($rh->structure)->create();

    $this->post("/qvct/questionnaires/{$q->id}/campaigns", [
        'opens_at' => '2026-05-10',
        'closes_at' => '2026-05-01',
    ])->assertSessionHasErrors('closes_at');
});

it('rh can list campaigns', function (): void {
    $rh = actingAsRole('rh');
    QvctCampaign::factory()->forStructure($rh->structure)->count(2)->create();

    $this->get('/qvct/campaigns')->assertSuccessful();
});

it('rh can view a campaign without seeing individual responses', function (): void {
    $rh = actingAsRole('rh');
    $campaign = QvctCampaign::factory()->forStructure($rh->structure)->create();
    QvctResponse::factory()->forCampaign($campaign)->count(5)->create();

    $response = $this->get("/qvct/campaigns/{$campaign->id}");
    $response->assertSuccessful();
    // Inertia props expose response_count (an aggregate); the view never lists
    // individual response rows. Anonymity invariant.
    $props = $response->viewData('page')['props'] ?? [];
    expect($props['response_count'] ?? null)->toBe(5);
});

it('rh can close a campaign which triggers detection', function (): void {
    $rh = actingAsRole('rh');
    $q = QvctQuestionnaire::factory()->forStructure($rh->structure)->create();
    $campaign = QvctCampaign::factory()
        ->forStructure($rh->structure)
        ->create([
            'questionnaire_id' => $q->id,
            'status' => QvctCampaignStatus::Active->value,
        ]);

    QvctResponse::factory()->forCampaign($campaign)->count(3)->create([
        'answers' => ['morale' => 1, 'charge' => 1, 'relations' => 5, 'isolement' => 5],
        'team_tag' => null,
    ]);

    $this->post("/qvct/campaigns/{$campaign->id}/close")->assertRedirect();

    $fresh = $campaign->fresh();
    expect($fresh->status)->toBe(QvctCampaignStatus::Closed);
    expect($fresh->weakSignals()->count())->toBe(2);
});

it('cannot read a campaign from another tenant', function (): void {
    actingAsRole('rh');
    $foreign = QvctCampaign::factory()->forStructure(Structure::factory()->create())->create();

    $this->get("/qvct/campaigns/{$foreign->id}")->assertNotFound();
});

it('cannot close a campaign from another tenant', function (): void {
    actingAsRole('rh');
    $foreign = QvctCampaign::factory()->forStructure(Structure::factory()->create())->create();

    $this->post("/qvct/campaigns/{$foreign->id}/close")->assertNotFound();
});
