<?php

declare(strict_types=1);

use App\Enums\ActionStatus;
use App\Enums\PacSource;
use App\Enums\PlanAmeliorationStatus;
use App\Models\ActionAmelioration;
use App\Models\PlanAmelioration;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('blocks a coordinateur from listing PAC (only quality+executive view)', function () {
    actingAsRole('coordinateur');

    $this->get('/plans-amelioration')->assertSuccessful();
});

it('lets a referent_qualite list PAC', function () {
    actingAsRole('referent_qualite');

    $this->get('/plans-amelioration')->assertSuccessful();
});

it('lets a dirigeant create a PAC', function () {
    actingAsRole('dirigeant');

    $this->post('/plans-amelioration', [
        'titre' => 'Plan test',
        'source' => PacSource::Audit->value,
        'echeance' => now()->addWeeks(2)->format('Y-m-d'),
    ])->assertRedirect();

    expect(PlanAmelioration::query()->where('titre', 'Plan test')->exists())->toBeTrue();
});

it('adds an action to a PAC and promotes it to en_cours', function () {
    $user = actingAsRole('referent_qualite');
    $plan = PlanAmelioration::factory()->forStructure($user->structure)->createdBy($user)->create();

    $this->post("/plans-amelioration/{$plan->id}/actions", [
        'description' => 'Action concrète',
    ])->assertRedirect();

    expect($plan->fresh()->statut)->toBe(PlanAmeliorationStatus::EnCours)
        ->and($plan->actions()->count())->toBe(1);
});

it('marks an action as done with a server-side timestamp', function () {
    $user = actingAsRole('referent_qualite');
    $plan = PlanAmelioration::factory()->forStructure($user->structure)->createdBy($user)->create();
    $action = ActionAmelioration::factory()->forPlan($plan)->create();

    $this->post("/plans-amelioration/{$plan->id}/actions/{$action->id}/done")->assertRedirect();

    $action->refresh();
    expect($action->statut)->toBe(ActionStatus::Realisee)
        ->and($action->realise_at)->not->toBeNull();
});

it('closes a PAC and rejects further action mutations', function () {
    $user = actingAsRole('dirigeant');
    $plan = PlanAmelioration::factory()->forStructure($user->structure)->createdBy($user)->enCours()->create();

    $this->post("/plans-amelioration/{$plan->id}/close")->assertRedirect();

    $plan->refresh();
    expect($plan->statut)->toBe(PlanAmeliorationStatus::Termine);

    // Cannot add action to a closed plan (policy blocks first → 403).
    $this->post("/plans-amelioration/{$plan->id}/actions", [
        'description' => 'Trop tard',
    ])->assertForbidden();

    expect($plan->fresh()->actions()->count())->toBe(0);
});

it('lets a referent_qualite open the edit page for an open PAC', function () {
    $user = actingAsRole('referent_qualite');
    $plan = PlanAmelioration::factory()->forStructure($user->structure)->createdBy($user)->create();

    $this->get("/plans-amelioration/{$plan->id}/edit")
        ->assertSuccessful()
        ->assertInertia(fn ($p) => $p
            ->component('dashboard/plans-amelioration/edit')
            ->where('plan.id', $plan->id));
});

it('blocks the edit page once the PAC is closed', function () {
    $user = actingAsRole('dirigeant');
    $plan = PlanAmelioration::factory()->forStructure($user->structure)->createdBy($user)->termine()->create();

    $this->get("/plans-amelioration/{$plan->id}/edit")->assertForbidden();
});

it('updates the PAC metadata via PUT', function () {
    $user = actingAsRole('referent_qualite');
    $plan = PlanAmelioration::factory()->forStructure($user->structure)->createdBy($user)->create();

    $this->put("/plans-amelioration/{$plan->id}", [
        'titre' => 'Plan révisé',
        'responsable' => 'Anne Petit',
    ])->assertRedirect();

    expect($plan->fresh()->titre)->toBe('Plan révisé')
        ->and($plan->fresh()->responsable)->toBe('Anne Petit');
});

it('does not leak PAC across tenants', function () {
    ['userA' => $userA, 'userB' => $userB, 'structureA' => $sa, 'structureB' => $sb] = twoStructures();

    app(PermissionRegistrar::class)->setPermissionsTeamId($sa->getKey());
    $userA->syncRoles([]);
    $userA->update(['type' => 'referent_qualite']);
    $userA->assignRole('referent_qualite');

    app(PermissionRegistrar::class)->setPermissionsTeamId($sb->getKey());
    $userB->syncRoles([]);
    $userB->update(['type' => 'referent_qualite']);
    $userB->assignRole('referent_qualite');

    PlanAmelioration::factory()->forStructure($sa)->createdBy($userA)->count(2)->create();
    PlanAmelioration::factory()->forStructure($sb)->createdBy($userB)->count(3)->create();

    actingAsStructure($userA);
    expect(PlanAmelioration::query()->count())->toBe(2);

    actingAsStructure($userB);
    expect(PlanAmelioration::query()->count())->toBe(3);
});
