<?php

declare(strict_types=1);

use App\Enums\QvctCampaignStatus;
use App\Models\QvctCampaign;
use App\Models\QvctQuestionnaire;
use App\Models\QvctResponse;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('lets an intervenant submit a response to an open campaign via the web', function (): void {
    $user = actingAsRole('intervenant');
    $q = QvctQuestionnaire::factory()->forStructure($user->structure)->create();
    $campaign = QvctCampaign::factory()->create([
        'structure_id' => $user->structure_id,
        'questionnaire_id' => $q->id,
        'status' => QvctCampaignStatus::Active,
        'launched_by' => $user->id,
    ]);

    $this->post("/qvct/campaigns/{$campaign->id}/respond", [
        'answers' => ['morale' => 4, 'charge' => 3],
    ])->assertRedirect('/qvct');

    expect(QvctResponse::query()->where('campaign_id', $campaign->id)->count())->toBe(1);
});

it('forbids a dirigeant (no qvct.respond) from submitting', function (): void {
    $user = actingAsRole('dirigeant');
    $q = QvctQuestionnaire::factory()->forStructure($user->structure)->create();
    $campaign = QvctCampaign::factory()->create([
        'structure_id' => $user->structure_id,
        'questionnaire_id' => $q->id,
        'status' => QvctCampaignStatus::Active,
        'launched_by' => $user->id,
    ]);

    $this->post("/qvct/campaigns/{$campaign->id}/respond", [
        'answers' => ['morale' => 4],
    ])->assertForbidden();
});

it('rejects a response on a closed campaign (service guard, 409)', function (): void {
    $user = actingAsRole('intervenant');
    $q = QvctQuestionnaire::factory()->forStructure($user->structure)->create();
    $campaign = QvctCampaign::factory()->create([
        'structure_id' => $user->structure_id,
        'questionnaire_id' => $q->id,
        'status' => QvctCampaignStatus::Closed,
        'launched_by' => $user->id,
    ]);

    $this->post("/qvct/campaigns/{$campaign->id}/respond", [
        'answers' => ['morale' => 4],
    ])->assertStatus(409);
});

it('renders the questionnaire page with the latest open campaign for the tenant', function (): void {
    $user = actingAsRole('intervenant');
    $q = QvctQuestionnaire::factory()->forStructure($user->structure)->create([
        'title' => 'Modèle test',
        'questions' => [
            ['key' => 'morale', 'label' => 'Votre moral ?', 'scale' => '1-5', 'category' => 'morale'],
        ],
    ]);
    QvctCampaign::factory()->create([
        'structure_id' => $user->structure_id,
        'questionnaire_id' => $q->id,
        'status' => QvctCampaignStatus::Active,
        'title' => 'Baromètre — Mai 2026',
        'launched_by' => $user->id,
    ]);

    $this->get('/qvct/questionnaire')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('dashboard/qvct/questionnaire')
            ->where('campagne.titre', 'Baromètre — Mai 2026')
            ->where('questions.0.id', 'morale')
            ->where('questions.0.type', 'likert'));
});
