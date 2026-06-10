<?php

use App\Enums\CategorieIncident;
use App\Enums\StatutIncident;
use App\Models\Incident;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    $this->seed(RoleSeeder::class);
});

// ── STORE ──────────────────────────────────────────────────────────────────────

it('intervenant can declare an incident', function () {
    $intervenant = actingAsRole('intervenant');

    $response = $this->post('/incidents', [
        'occurred_at' => now()->subHour()->toDateTimeString(),
        'categorie' => CategorieIncident::Chute->value,
        'description' => 'Bénéficiaire a chuté en sortant du lit.',
    ]);

    $response->assertRedirect();
    expect(Incident::count())->toBe(1);
    expect(Incident::first()->declared_by)->toBe($intervenant->id);
});

it('rejects declaration without description', function () {
    actingAsRole('intervenant');

    $response = $this->post('/incidents', [
        'occurred_at' => now()->subHour()->toDateTimeString(),
        'categorie' => CategorieIncident::Chute->value,
    ]);

    $response->assertSessionHasErrors('description');
    expect(Incident::count())->toBe(0);
});

it('rejects a future occurred_at', function () {
    actingAsRole('intervenant');

    $response = $this->post('/incidents', [
        'occurred_at' => now()->addDay()->toDateTimeString(),
        'categorie' => CategorieIncident::Chute->value,
        'description' => 'Incident dans le futur.',
    ]);

    $response->assertSessionHasErrors('occurred_at');
});

// ── ASSIGN ─────────────────────────────────────────────────────────────────────

it('coordinator can assign an incident', function () {
    $coord = actingAsRole('coordinateur');
    $incident = Incident::factory()
        ->forStructure($coord->structure)
        ->declaredBy($coord)
        ->create();

    $response = $this->post("/incidents/{$incident->id}/assign", [
        'coordinateur_id' => $coord->id,
    ]);

    $response->assertRedirect();
    expect($incident->fresh()->statut)->toBe(StatutIncident::EnAnalyse);
});

it('intervenant cannot assign an incident', function () {
    $intervenant = actingAsRole('intervenant');
    $incident = Incident::factory()
        ->forStructure($intervenant->structure)
        ->declaredBy($intervenant)
        ->create();

    $response = $this->post("/incidents/{$incident->id}/assign", [
        'coordinateur_id' => $intervenant->id,
    ]);

    $response->assertForbidden();
});

// ── ANALYSE ────────────────────────────────────────────────────────────────────

it('coordinator can record 5-whys analysis', function () {
    $coord = actingAsRole('coordinateur');
    $incident = Incident::factory()
        ->forStructure($coord->structure)
        ->declaredBy($coord)
        ->enAnalyse()
        ->create();

    $response = $this->post("/incidents/{$incident->id}/analyse", [
        'analyse_causes' => 'Pourquoi 1: sol mouillé. Pourquoi 2: absence de signalisation.',
    ]);

    $response->assertRedirect();
    expect($incident->fresh()->statut)->toBe(StatutIncident::PlanActions);
});

it('supports the show-page flow: declare → assign self → analyse', function () {
    // Mirrors the "Démarrer l'analyse" button which assigns the incident to
    // the acting coordinateur (declare → en_analyse) before the 5-whys form
    // is posted. Regression guard for the assigned_to/coordinateur_id field
    // mismatch that left the incident stuck in `declare` (HTTP 409).
    $coord = actingAsRole('coordinateur');
    $incident = Incident::factory()
        ->forStructure($coord->structure)
        ->declaredBy($coord)
        ->create();

    expect($incident->statut)->toBe(StatutIncident::Declare);

    $this->post("/incidents/{$incident->id}/assign", [
        'coordinateur_id' => $coord->id,
    ])->assertRedirect();

    expect($incident->fresh()->statut)->toBe(StatutIncident::EnAnalyse);

    $this->post("/incidents/{$incident->id}/analyse", [
        'analyse_causes' => 'Pourquoi 1: sol mouillé. Pourquoi 2: absence de signalisation.',
    ])->assertRedirect();

    expect($incident->fresh()->statut)->toBe(StatutIncident::PlanActions);
});

it('rejects analysis with fewer than 20 characters', function () {
    $coord = actingAsRole('coordinateur');
    $incident = Incident::factory()
        ->forStructure($coord->structure)
        ->declaredBy($coord)
        ->enAnalyse()
        ->create();

    $response = $this->post("/incidents/{$incident->id}/analyse", [
        'analyse_causes' => 'Trop court.',
    ]);

    $response->assertSessionHasErrors('analyse_causes');
});

// ── CLOSE ──────────────────────────────────────────────────────────────────────

it('coordinator can close an incident', function () {
    $coord = actingAsRole('coordinateur');
    $incident = Incident::factory()
        ->forStructure($coord->structure)
        ->declaredBy($coord)
        ->planActions()
        ->create();

    $response = $this->post("/incidents/{$incident->id}/close", [
        'closing_note' => 'Toutes les actions correctives ont été menées à bien.',
    ]);

    $response->assertRedirect();
    expect($incident->fresh()->statut)->toBe(StatutIncident::Clos);
});

it('requires a closing note to close', function () {
    $coord = actingAsRole('coordinateur');
    $incident = Incident::factory()
        ->forStructure($coord->structure)
        ->declaredBy($coord)
        ->planActions()
        ->create();

    $response = $this->post("/incidents/{$incident->id}/close");

    $response->assertSessionHasErrors('closing_note');
    expect($incident->fresh()->statut)->toBe(StatutIncident::PlanActions);
});

it('forbids closing an already closed incident', function () {
    $coord = actingAsRole('coordinateur');
    $incident = Incident::factory()
        ->forStructure($coord->structure)
        ->declaredBy($coord)
        ->clos()
        ->create();

    $response = $this->post("/incidents/{$incident->id}/close", [
        'closing_note' => 'Tentative de re-clôture.',
    ]);

    $response->assertForbidden();
});

// ── CROSS-TENANT ───────────────────────────────────────────────────────────────

it('returns 404 when accessing a foreign structure incident', function () {
    actingAsRole('coordinateur');
    $other = Structure::factory()->create();
    $foreignIncident = Incident::factory()->forStructure($other)->create([
        'declared_by' => User::factory()->create(['structure_id' => $other->id])->id,
    ]);

    $this->get("/incidents/{$foreignIncident->id}")->assertNotFound();
    $this->put("/incidents/{$foreignIncident->id}", ['description' => 'hack'])->assertNotFound();
    $this->delete("/incidents/{$foreignIncident->id}")->assertNotFound();
});

// ── SOFT DELETE ────────────────────────────────────────────────────────────────

it('coordinator can soft-delete an incident', function () {
    $coord = actingAsRole('coordinateur');
    $incident = Incident::factory()
        ->forStructure($coord->structure)
        ->declaredBy($coord)
        ->create();

    $response = $this->delete("/incidents/{$incident->id}");

    $response->assertRedirect();
    expect(Incident::count())->toBe(0)
        ->and(Incident::withTrashed()->count())->toBe(1);
});

// ── INDEX (Inertia page contract) ──────────────────────────────────────────────

it('index page returns incidents in the shape the React component expects', function () {
    $coord = actingAsRole('coordinateur');
    $declarant = User::factory()->create([
        'structure_id' => $coord->structure_id,
        'first_name' => 'Sophie',
        'last_name' => 'Bernard',
        'type' => 'intervenant',
    ]);

    Incident::factory()
        ->forStructure($coord->structure)
        ->state([
            'declared_by' => $declarant->id,
            'categorie' => CategorieIncident::Chute->value,
            'gravite' => 'grave',
            'statut' => StatutIncident::Declare->value,
            'notifie_ars_at' => now(),
        ])
        ->create();

    $response = $this->get('/incidents');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('dashboard/incidents/index')
        ->has('incidents', 1)
        ->has('incidents.0', fn ($item) => $item
            ->where('initials', 'SB')
            ->where('declarant', 'Sophie Bernard')
            ->where('categorie', 'Chute')
            ->where('gravite', 'grave')
            ->where('statut', 'declare')
            ->where('notifie_autorites', true)
            ->etc()
        )
        ->where('total', 1)
        ->has('stats', fn ($stats) => $stats
            ->where('declare', 1)
            ->where('en_analyse', 0)
            ->where('plan_actions', 0)
            ->where('clos', 0)
            ->where('graves', 1)
        )
    );
});
