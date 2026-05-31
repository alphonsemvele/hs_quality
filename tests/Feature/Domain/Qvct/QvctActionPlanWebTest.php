<?php

declare(strict_types=1);

use App\Enums\QvctActionPlanItemStatus;
use App\Enums\QvctActionPlanStatus;
use App\Models\QvctActionPlan;
use App\Models\QvctActionPlanItem;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('renders the action-plans create form for a manager', function (): void {
    actingAsRole('dirigeant');

    $this->get('/qvct/action-plans/create')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('dashboard/qvct/action-plans/create'));
});

it('forbids the create form for an intervenant', function (): void {
    actingAsRole('intervenant');

    $this->get('/qvct/action-plans/create')->assertForbidden();
});

it('drafts a new action plan via the web form', function (): void {
    $user = actingAsRole('dirigeant');

    $this->post('/qvct/action-plans', [
        'title' => 'Plan secteur Nord — soutien',
        'description' => 'Suite au signal détecté en avril.',
        'target_quarter' => '2026-Q2',
    ])->assertRedirect();

    $plan = QvctActionPlan::query()->where('title', 'Plan secteur Nord — soutien')->firstOrFail();
    expect($plan->status)->toBe(QvctActionPlanStatus::Draft)
        ->and($plan->structure_id)->toBe($user->structure_id)
        ->and($plan->created_by)->toBe($user->id);
});

it('renders the action-plan show page with items', function (): void {
    $user = actingAsRole('dirigeant');
    $plan = QvctActionPlan::factory()->forStructure($user->structure)->create([
        'title' => 'Plan test',
        'created_by' => $user->id,
    ]);
    QvctActionPlanItem::factory()->create([
        'structure_id' => $user->structure_id,
        'action_plan_id' => $plan->id,
        'title' => 'Action A',
    ]);

    $this->get("/qvct/action-plans/{$plan->id}")
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('dashboard/qvct/action-plans/show')
            ->where('plan.title', 'Plan test')
            ->where('plan.items.0.title', 'Action A'));
});

it('adds an item to a draft plan', function (): void {
    $user = actingAsRole('dirigeant');
    $plan = QvctActionPlan::factory()->forStructure($user->structure)->create([
        'created_by' => $user->id,
        'status' => QvctActionPlanStatus::Draft,
    ]);

    $this->post("/qvct/action-plans/{$plan->id}/items", [
        'title' => 'Mettre en place doublure',
        'description' => 'Sur deux semaines',
    ])->assertRedirect();

    expect($plan->items()->count())->toBe(1)
        ->and($plan->items()->first()->title)->toBe('Mettre en place doublure');
});

it('publishes a draft plan', function (): void {
    $user = actingAsRole('dirigeant');
    $plan = QvctActionPlan::factory()->forStructure($user->structure)->create([
        'created_by' => $user->id,
        'status' => QvctActionPlanStatus::Draft,
    ]);

    $this->post("/qvct/action-plans/{$plan->id}/publish")->assertRedirect();

    expect($plan->fresh()->status)->toBe(QvctActionPlanStatus::Published);
});

it('updates an item status on a draft plan', function (): void {
    $user = actingAsRole('dirigeant');
    $plan = QvctActionPlan::factory()->forStructure($user->structure)->create([
        'created_by' => $user->id,
    ]);
    $item = QvctActionPlanItem::factory()->create([
        'structure_id' => $user->structure_id,
        'action_plan_id' => $plan->id,
        'status' => QvctActionPlanItemStatus::Pending,
    ]);

    $this->post("/qvct/action-plan-items/{$item->id}/status", [
        'status' => QvctActionPlanItemStatus::InProgress->value,
    ])->assertRedirect();

    expect($item->fresh()->status)->toBe(QvctActionPlanItemStatus::InProgress);
});

it('rejects an unknown item status (422)', function (): void {
    $user = actingAsRole('dirigeant');
    $plan = QvctActionPlan::factory()->forStructure($user->structure)->create([
        'created_by' => $user->id,
    ]);
    $item = QvctActionPlanItem::factory()->create([
        'structure_id' => $user->structure_id,
        'action_plan_id' => $plan->id,
    ]);

    $this->post("/qvct/action-plan-items/{$item->id}/status", [
        'status' => 'whatever',
    ])->assertStatus(422);
});

it('lists real plans on the QVCT action-plans index', function (): void {
    $user = actingAsRole('dirigeant');
    QvctActionPlan::factory()->forStructure($user->structure)->create([
        'title' => 'Plan réel — pas démo',
        'created_by' => $user->id,
    ]);

    $this->get('/qvct/action-plans')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('dashboard/qvct/action-plans/index')
            ->where('plans.0.title', 'Plan réel — pas démo'));
});
