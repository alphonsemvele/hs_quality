<?php

declare(strict_types=1);

use App\Models\QvctActionPlan;
use App\Models\QvctActionPlanItem;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
});

function makeApUser(string $role, Structure $structure): User
{
    $user = User::factory()->forStructure($structure)->state(['type' => $role])->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());
    $user->assignRole($role);

    return $user;
}

// ── Cross-tenant scope ────────────────────────────────────────────────────────

it('only returns action plans from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();

    QvctActionPlan::factory()->forStructure($a)->count(2)->create();
    QvctActionPlan::factory()->forStructure($b)->count(3)->create();

    app()->instance('current_structure', $a);
    expect(QvctActionPlan::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(QvctActionPlan::count())->toBe(3);
});

// ── Policy / RBAC ─────────────────────────────────────────────────────────────

it('rh can view + edit + delete action plans', function (): void {
    $rh = makeApUser('rh', $this->structure);
    $plan = QvctActionPlan::factory()->forStructure($this->structure)->create();

    expect($rh->can('view', $plan))->toBeTrue()
        ->and($rh->can('update', $plan))->toBeTrue()
        ->and($rh->can('create', QvctActionPlan::class))->toBeTrue()
        ->and($rh->can('delete', $plan))->toBeTrue();
});

it('coordinateur sees only published or closed plans', function (): void {
    $coord = makeApUser('coordinateur', $this->structure);

    $draft = QvctActionPlan::factory()->forStructure($this->structure)->create();
    $published = QvctActionPlan::factory()->forStructure($this->structure)->published()->create();
    $closed = QvctActionPlan::factory()->forStructure($this->structure)->closed()->create();

    expect($coord->can('view', $draft))->toBeFalse()
        ->and($coord->can('view', $published))->toBeTrue()
        ->and($coord->can('view', $closed))->toBeTrue()
        ->and($coord->can('update', $published))->toBeFalse();
});

it('intervenant cannot view action plans at all', function (): void {
    $intervenant = makeApUser('intervenant', $this->structure);
    $published = QvctActionPlan::factory()->forStructure($this->structure)->published()->create();

    expect($intervenant->can('viewAny', QvctActionPlan::class))->toBeFalse()
        ->and($intervenant->can('view', $published))->toBeFalse();
});

it('responsible_user can update their own item even without RH perms', function (): void {
    $responsible = makeApUser('coordinateur', $this->structure);
    $plan = QvctActionPlan::factory()->forStructure($this->structure)->published()->create();
    $item = QvctActionPlanItem::factory()->forActionPlan($plan)->create([
        'responsible_user_id' => $responsible->id,
    ]);

    expect($responsible->can('update', $item))->toBeTrue();
});

it('cannot view a foreign-tenant plan', function (): void {
    $foreignStructure = Structure::factory()->create();
    $foreignRh = makeApUser('rh', $foreignStructure);

    app()->instance('current_structure', $this->structure);
    $local = QvctActionPlan::factory()->forStructure($this->structure)->create();

    expect($foreignRh->can('view', $local))->toBeFalse();
});

// ── HTTP API ──────────────────────────────────────────────────────────────────

it('rh can create an action plan via API', function (): void {
    $rh = actingAsApiRole('rh');

    $response = $this->postJson('/api/v1/qvct/action-plans', [
        'title' => 'Plan QVCT Q3 2026',
        'description' => 'Réduction de la surcharge sur les tournées du soir.',
        'target_quarter' => 'Q3-2026',
    ]);

    $response->assertCreated();
    expect(QvctActionPlan::where('created_by', $rh->id)->count())->toBe(1);
});

it('rh can publish then close an action plan via API', function (): void {
    $rh = actingAsApiRole('rh');
    $plan = QvctActionPlan::factory()->forStructure($rh->structure)->create();

    $publish = $this->postJson("/api/v1/qvct/action-plans/{$plan->id}/publish");
    $publish->assertSuccessful();
    expect($publish->json('status'))->toBe('published');

    $close = $this->postJson("/api/v1/qvct/action-plans/{$plan->id}/close");
    $close->assertSuccessful();
    expect($close->json('status'))->toBe('closed');
});

it('rejects close on a draft plan with 409', function (): void {
    $rh = actingAsApiRole('rh');
    $plan = QvctActionPlan::factory()->forStructure($rh->structure)->create();

    $this->postJson("/api/v1/qvct/action-plans/{$plan->id}/close")
        ->assertStatus(409);
});

it('rh can add items to a draft plan via API', function (): void {
    $rh = actingAsApiRole('rh');
    $plan = QvctActionPlan::factory()->forStructure($rh->structure)->create();

    $response = $this->postJson("/api/v1/qvct/action-plans/{$plan->id}/items", [
        'title' => 'Action 1',
        'impact_measurement_target' => 'Score morale ≥ 3.5',
    ]);

    $response->assertCreated();
    expect(QvctActionPlanItem::where('action_plan_id', $plan->id)->count())->toBe(1);
});

it('intervenant cannot create an action plan', function (): void {
    actingAsApiRole('intervenant');

    $this->postJson('/api/v1/qvct/action-plans', [
        'title' => 'Forbidden',
    ])->assertForbidden();
});

it('coordinateur listing skips draft plans', function (): void {
    $coord = actingAsApiRole('coordinateur');

    QvctActionPlan::factory()->forStructure($coord->structure)->create();
    QvctActionPlan::factory()->forStructure($coord->structure)->published()->create();
    QvctActionPlan::factory()->forStructure($coord->structure)->closed()->create();

    $response = $this->getJson('/api/v1/qvct/action-plans');
    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2); // published + closed only
});

it('rh can record impact on an item', function (): void {
    $rh = actingAsApiRole('rh');
    $plan = QvctActionPlan::factory()->forStructure($rh->structure)->published()->create();
    $item = QvctActionPlanItem::factory()->forActionPlan($plan)->create();

    $response = $this->postJson("/api/v1/qvct/action-plan-items/{$item->id}/impact", [
        'impact_measurement_actual' => 'Score morale 3.7 atteint en juin.',
    ]);

    $response->assertSuccessful();
    expect($item->fresh()->impact_measurement_actual)->toBe('Score morale 3.7 atteint en juin.');
});
