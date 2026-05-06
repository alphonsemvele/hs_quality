<?php

declare(strict_types=1);

use App\Enums\ActionStatus;
use App\Enums\PacSource;
use App\Enums\PlanAmeliorationStatus;
use App\Models\ActionAmelioration;
use App\Models\PlanAmelioration;
use App\Models\Structure;
use App\Models\User;
use App\Services\PlanAmeliorationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create();
    $this->qualite = User::factory()->forStructure($this->structure)->state(['type' => 'referent_qualite'])->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($this->structure->getKey());
    $this->qualite->assignRole('referent_qualite');

    app()->instance('current_structure', $this->structure);

    $this->service = new PlanAmeliorationService;
});

it('creates a PAC in ouvert state', function () {
    $plan = $this->service->create([
        'titre' => 'Plan test',
        'source' => PacSource::Incident->value,
    ], $this->qualite);

    expect($plan->statut)->toBe(PlanAmeliorationStatus::Ouvert)
        ->and($plan->structure_id)->toBe($this->structure->id);
});

it('promotes the PAC from ouvert to en_cours when first action is added', function () {
    $plan = PlanAmelioration::factory()->forStructure($this->structure)->createdBy($this->qualite)->create();

    $this->service->addAction($plan, ['description' => 'Action test']);

    expect($plan->fresh()->statut)->toBe(PlanAmeliorationStatus::EnCours);
});

it('marks an action as done with a timestamp', function () {
    $plan = PlanAmelioration::factory()->forStructure($this->structure)->createdBy($this->qualite)->create();
    $action = ActionAmelioration::factory()->forPlan($plan)->create();

    $done = $this->service->markActionDone($action);

    expect($done->statut)->toBe(ActionStatus::Realisee)
        ->and($done->realise_at)->not->toBeNull();
});

it('clears the realise_at timestamp when reverting an action to non-done', function () {
    $plan = PlanAmelioration::factory()->forStructure($this->structure)->createdBy($this->qualite)->create();
    $action = ActionAmelioration::factory()->forPlan($plan)->realisee()->create();

    $reverted = $this->service->updateAction($action, ['statut' => ActionStatus::EnCours->value]);

    expect($reverted->statut)->toBe(ActionStatus::EnCours)
        ->and($reverted->realise_at)->toBeNull();
});

it('refuses to add actions to a closed PAC', function () {
    $plan = PlanAmelioration::factory()->forStructure($this->structure)->createdBy($this->qualite)->termine()->create();

    $this->service->addAction($plan, ['description' => 'Trop tard']);
})->throws(HttpException::class);

it('closes a plan and stamps closed_at', function () {
    $plan = PlanAmelioration::factory()->forStructure($this->structure)->createdBy($this->qualite)->enCours()->create();

    $closed = $this->service->close($plan, null);

    expect($closed->statut)->toBe(PlanAmeliorationStatus::Termine)
        ->and($closed->closed_at)->not->toBeNull();
});

it('computes 0% progression when there are no actions', function () {
    $plan = PlanAmelioration::factory()->forStructure($this->structure)->createdBy($this->qualite)->create();

    expect($plan->progression())->toBe(0);
});

it('computes progression % from realised over non-cancelled actions', function () {
    $plan = PlanAmelioration::factory()->forStructure($this->structure)->createdBy($this->qualite)->create();
    ActionAmelioration::factory()->forPlan($plan)->realisee()->create();
    ActionAmelioration::factory()->forPlan($plan)->realisee()->create();
    ActionAmelioration::factory()->forPlan($plan)->create(); // planifiée
    ActionAmelioration::factory()->forPlan($plan)->state(['statut' => ActionStatus::Annulee->value])->create(); // ignored

    expect($plan->progression())->toBe(67); // 2/3 of non-cancelled = 66.67 → 67
});
