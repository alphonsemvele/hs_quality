<?php

declare(strict_types=1);

use App\Models\QvctCampaign;
use App\Models\QvctQuestionnaire;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('renders the questionnaires index page component', function (): void {
    $user = actingAsRole('dirigeant');
    QvctQuestionnaire::factory()->forStructure($user->structure)->create();

    $this->get('/qvct/questionnaires')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('dashboard/qvct/questionnaires/index'));
});

it('renders the questionnaire show page component', function (): void {
    $user = actingAsRole('dirigeant');
    $q = QvctQuestionnaire::factory()->forStructure($user->structure)->create();

    $this->get("/qvct/questionnaires/{$q->id}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('dashboard/qvct/questionnaires/show'));
});

it('renders the campaigns index page component', function (): void {
    actingAsRole('dirigeant');

    $this->get('/qvct/campaigns')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('dashboard/qvct/campaigns/index'));
});

it('renders the campaign show page component', function (): void {
    $user = actingAsRole('dirigeant');
    $q = QvctQuestionnaire::factory()->forStructure($user->structure)->create();
    $c = QvctCampaign::factory()->create([
        'structure_id' => $user->structure_id,
        'questionnaire_id' => $q->id,
        'launched_by' => $user->id,
    ]);

    $this->get("/qvct/campaigns/{$c->id}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('dashboard/qvct/campaigns/show'));
});
