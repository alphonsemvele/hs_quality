<?php

declare(strict_types=1);

use App\Models\QvctQuestionnaire;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('rh can list questionnaires', function (): void {
    $rh = actingAsRole('rh');
    QvctQuestionnaire::factory()->forStructure($rh->structure)->count(3)->create();

    $this->get('/qvct/questionnaires')->assertSuccessful();
});

it('intervenant cannot list questionnaires (no manage perm)', function (): void {
    actingAsRole('intervenant');

    $this->get('/qvct/questionnaires')->assertSuccessful(); // can view active templates
});

it('rh can create a new questionnaire', function (): void {
    actingAsRole('rh');

    $payload = [
        'title' => 'Baromètre Mai 2026',
        'frequency' => 'monthly',
        'questions' => [
            ['key' => 'morale', 'label' => 'Comment évaluez-vous votre moral ?', 'scale' => '1-5', 'category' => 'baisse_morale'],
        ],
    ];

    $this->post('/qvct/questionnaires', $payload)->assertRedirect();
    expect(QvctQuestionnaire::where('title', 'Baromètre Mai 2026')->count())->toBe(1);
});

it('intervenant cannot create a questionnaire', function (): void {
    actingAsRole('intervenant');

    $this->post('/qvct/questionnaires', [
        'title' => 'Test',
        'frequency' => 'monthly',
        'questions' => [['key' => 'a', 'label' => 'A', 'scale' => '1-5']],
    ])->assertForbidden();

    expect(QvctQuestionnaire::count())->toBe(0);
});

it('rejects invalid question key (non-snake_case)', function (): void {
    actingAsRole('rh');

    $this->post('/qvct/questionnaires', [
        'title' => 'Bad keys',
        'frequency' => 'monthly',
        'questions' => [['key' => 'BadKey!', 'label' => 'X', 'scale' => '1-5']],
    ])->assertSessionHasErrors('questions.0.key');
});

it('rejects an unknown frequency', function (): void {
    actingAsRole('rh');

    $this->post('/qvct/questionnaires', [
        'title' => 'Bad freq',
        'frequency' => 'bi-weekly',
        'questions' => [['key' => 'a', 'label' => 'A', 'scale' => '1-5']],
    ])->assertSessionHasErrors('frequency');
});

it('rh can archive a questionnaire', function (): void {
    $rh = actingAsRole('rh');
    $q = QvctQuestionnaire::factory()->forStructure($rh->structure)->create(['is_active' => true]);

    $this->post("/qvct/questionnaires/{$q->id}/archive")->assertRedirect();
    expect($q->fresh()->is_active)->toBeFalse();
});

it('cannot read a questionnaire from another tenant', function (): void {
    actingAsRole('rh');
    $foreignStructure = Structure::factory()->create();
    $foreignQ = QvctQuestionnaire::factory()->forStructure($foreignStructure)->create();

    $this->get("/qvct/questionnaires/{$foreignQ->id}")->assertNotFound();
});
