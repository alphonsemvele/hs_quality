<?php

use App\Enums\QvctMood;
use App\Enums\QvctWeakSignalType;
use App\Models\QvctWeakSignal;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('renders the QVCT hub with trend, weak signals and team breakdown', function () {
    actingAsRole('rh');

    $response = $this->get('/qvct');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];

    expect($props)->toHaveKeys(['campagnes', 'stats', 'trend', 'weak_signals', 'team_breakdown']);
});

it('renders the QVCT weak signals triage page', function () {
    actingAsRole('rh');

    $response = $this->get('/qvct/weak-signals');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];

    expect($props)->toHaveKeys(['signals', 'stats']);
    expect($props['stats'])->toHaveKeys(['total', 'unacknowledged', 'critical', 'this_week']);
});

it('renders the QVCT RPS heatmap dashboard', function () {
    actingAsRole('rh');

    $response = $this->get('/qvct/indicators');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];

    expect($props)->toHaveKeys(['dimensions', 'matrix', 'trend']);
    expect($props['matrix'])->toHaveKeys(['teams', 'cells']);
});

it('renders the QVCT action plans index', function () {
    actingAsRole('rh');

    $response = $this->get('/qvct/action-plans');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];

    expect($props)->toHaveKeys(['plans', 'stats']);
});

it('renders the personal QVCT journal', function () {
    actingAsRole('intervenant');

    $response = $this->get('/qvct/journal');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];

    expect($props)->toHaveKeys(['entries', 'shared_count']);
});

it('accepts a journal entry submission and redirects', function () {
    actingAsRole('intervenant');

    // StoreJournalEntryRequest validates 'body' (not 'content') and expects
    // a QvctMood string value (not an integer).
    $response = $this->post('/qvct/journal', [
        'mood' => QvctMood::Positif->value,
        'body' => 'Bonne journée — tournée bien menée.',
        'shared_with_rh' => false,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

it('renders the exchange requests inbox', function () {
    actingAsRole('rh');

    $response = $this->get('/qvct/exchanges');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];

    expect($props)->toHaveKeys(['inbox', 'outbox']);
});

it('accepts a new exchange request submission', function () {
    actingAsRole('intervenant');

    $response = $this->post('/qvct/exchanges', [
        'addressee_role' => 'rh',
        'reason' => 'Charge de travail',
        'message' => 'Souhaite échanger sur la charge.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

it('acknowledges a weak signal via POST', function () {
    actingAsRole('rh');

    $structure = currentStructure();
    $signal = QvctWeakSignal::factory()->create([
        'structure_id' => $structure->id,
        'signal_type' => QvctWeakSignalType::Surcharge->value,
        'severity' => 7,
    ]);

    $response = $this->post("/qvct/weak-signals/{$signal->id}/acknowledge");

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

it('rejects unauthenticated access to QVCT pages', function () {
    $this->get('/qvct')->assertRedirect('/login');
    $this->get('/qvct/weak-signals')->assertRedirect('/login');
    $this->get('/qvct/indicators')->assertRedirect('/login');
    $this->get('/qvct/journal')->assertRedirect('/login');
    $this->get('/qvct/exchanges')->assertRedirect('/login');
});
