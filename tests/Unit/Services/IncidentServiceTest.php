<?php

use App\Enums\CategorieIncident;
use App\Enums\GraviteIncident;
use App\Enums\StatutIncident;
use App\Events\IncidentDeclared;
use App\Jobs\NotifyARSJob;
use App\Jobs\NotifyResponsableSecteurJob;
use App\Models\Incident;
use App\Models\Structure;
use App\Models\User;
use App\Services\IncidentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->declarant = User::factory()->create([
        'structure_id' => $this->structure->id,
        'type' => 'intervenant',
    ]);

    $this->service = app(IncidentService::class);
});

it('declares an incident with auto-classified gravity', function () {
    Queue::fake();

    $incident = $this->service->declare([
        'occurred_at' => now()->subHour(),
        'categorie' => CategorieIncident::ErreurMedicamenteuse->value,
        'description' => 'Mauvais médicament administré.',
        'avec_deces' => false,
        'avec_hospitalisation' => false,
        'avec_blessure_physique' => false,
    ], $this->declarant);

    expect($incident)->toBeInstanceOf(Incident::class)
        ->and($incident->gravite)->toBe(GraviteIncident::Grave)
        ->and($incident->statut)->toBe(StatutIncident::Declare)
        ->and($incident->structure_id)->toBe($this->structure->id)
        ->and($incident->declared_by)->toBe($this->declarant->id);
});

it('dispatches IncidentDeclared event on declaration', function () {
    Event::fake([IncidentDeclared::class]);
    Queue::fake();

    $incident = $this->service->declare([
        'occurred_at' => now()->subHour(),
        'categorie' => CategorieIncident::Chute->value,
        'description' => 'Chute sans blessure.',
    ], $this->declarant);

    Event::assertDispatched(IncidentDeclared::class, fn ($e) => $e->incident->id === $incident->id);
});

it('always queues NotifyResponsableSecteurJob', function () {
    Queue::fake();

    $this->service->declare([
        'occurred_at' => now()->subHour(),
        'categorie' => CategorieIncident::Chute->value,
        'description' => 'Chute mineure.',
    ], $this->declarant);

    Queue::assertPushed(NotifyResponsableSecteurJob::class);
});

it('queues NotifyARSJob only for grave/critique incidents', function () {
    Queue::fake();

    $this->service->declare([
        'occurred_at' => now()->subHour(),
        'categorie' => CategorieIncident::SituationDanger->value,
        'description' => 'Situation critique.',
    ], $this->declarant);

    Queue::assertPushed(NotifyARSJob::class);
});

it('does not queue NotifyARSJob for mineur incidents', function () {
    Queue::fake();

    $this->service->declare([
        'occurred_at' => now()->subHour(),
        'categorie' => CategorieIncident::Chute->value,
        'description' => 'Petite chute sans conséquence.',
    ], $this->declarant);

    Queue::assertNotPushed(NotifyARSJob::class);
});

it('assigns a coordinateur and moves to en_analyse', function () {
    Queue::fake();
    $incident = Incident::factory()->declaredBy($this->declarant)->create();
    $coord = User::factory()->create(['structure_id' => $this->structure->id]);

    $updated = $this->service->assign($incident, $coord);

    expect($updated->statut)->toBe(StatutIncident::EnAnalyse)
        ->and($updated->assigned_to)->toBe($coord->id);
});

it('refuses to assign a closed incident', function () {
    Queue::fake();
    $incident = Incident::factory()->declaredBy($this->declarant)->clos()->create();
    $coord = User::factory()->create(['structure_id' => $this->structure->id]);

    expect(fn () => $this->service->assign($incident, $coord))
        ->toThrow(HttpException::class);
});

it('launches analysis and moves to plan_actions', function () {
    Queue::fake();
    $incident = Incident::factory()
        ->declaredBy($this->declarant)
        ->enAnalyse()
        ->create();

    $updated = $this->service->launchAnalysis($incident, 'Pourquoi 1: ... Pourquoi 2: ...');

    expect($updated->statut)->toBe(StatutIncident::PlanActions)
        ->and($updated->analyse_causes)->not->toBeNull();
});

it('refuses analysis when not in en_analyse status', function () {
    Queue::fake();
    $incident = Incident::factory()->declaredBy($this->declarant)->create();

    expect(fn () => $this->service->launchAnalysis($incident, 'Analyse'))
        ->toThrow(HttpException::class);
});

it('closes an incident with a suivi note', function () {
    Queue::fake();
    $incident = Incident::factory()->declaredBy($this->declarant)->planActions()->create();

    $updated = $this->service->close($incident, 'Incident résolu, actions menées.', $this->declarant);

    expect($updated->statut)->toBe(StatutIncident::Clos)
        ->and($updated->closed_at)->not->toBeNull();
    expect($incident->suivis()->count())->toBe(1);
});

it('refuses to close an already closed incident', function () {
    Queue::fake();
    $incident = Incident::factory()->declaredBy($this->declarant)->clos()->create();

    expect(fn () => $this->service->close($incident, 'Re-close attempt', $this->declarant))
        ->toThrow(HttpException::class);
});

it('soft-deletes an incident', function () {
    Queue::fake();
    $incident = Incident::factory()->declaredBy($this->declarant)->create();

    $this->service->delete($incident);

    expect(Incident::count())->toBe(0)
        ->and(Incident::withTrashed()->count())->toBe(1);
});
