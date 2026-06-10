<?php

declare(strict_types=1);

use App\Enums\TrainingPlanStatus;
use App\Models\TrainingPlan;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('renders the create-plan form for a dirigeant', function (): void {
    actingAsRole('dirigeant');

    $this->get('/formations/plans/create')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('dashboard/formations/plans/create'));
});

it('forbids the create-plan form for an intervenant', function (): void {
    actingAsRole('intervenant');

    $this->get('/formations/plans/create')->assertForbidden();
});

it('stores a new training plan via the web form', function (): void {
    $user = actingAsRole('dirigeant');

    $this->post('/formations', [
        'year' => 2026,
        'theme' => 'Bientraitance et prévention RPS',
        'target_audience' => 'Tous intervenants',
    ])
        ->assertRedirect('/formations')
        ->assertSessionHas('success');

    $plan = TrainingPlan::query()->where('theme', 'Bientraitance et prévention RPS')->firstOrFail();
    expect($plan->year)->toBe(2026)
        ->and($plan->target_audience)->toBe('Tous intervenants')
        ->and($plan->status)->toBe(TrainingPlanStatus::Draft)
        ->and($plan->structure_id)->toBe($user->structure_id)
        ->and($plan->created_by)->toBe($user->id);
});

it('rejects a create with missing required fields', function (): void {
    actingAsRole('dirigeant');

    $this->from('/formations/plans/create')->post('/formations', [])
        ->assertRedirect('/formations/plans/create')
        ->assertSessionHasErrors(['year', 'theme']);
});

it('renders the edit-plan form prefilled with current values', function (): void {
    $user = actingAsRole('dirigeant');
    $plan = TrainingPlan::factory()->forStructure($user->structure)->create([
        'year' => 2026,
        'theme' => 'PSC1 — Rappels secourisme',
    ]);

    $this->get("/formations/plans/{$plan->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('dashboard/formations/plans/edit')
            ->where('plan.year', 2026)
            ->where('plan.theme', 'PSC1 — Rappels secourisme'));
});

it('updates a plan via the web form', function (): void {
    $user = actingAsRole('dirigeant');
    $plan = TrainingPlan::factory()->forStructure($user->structure)->create([
        'theme' => 'Ancien thème',
    ]);

    $this->put("/formations/{$plan->id}", [
        'year' => 2027,
        'theme' => 'Nouveau thème',
        'target_audience' => 'Référents qualité',
    ])
        ->assertRedirect('/formations')
        ->assertSessionHas('success');

    $fresh = $plan->fresh();
    expect($fresh->year)->toBe(2027)
        ->and($fresh->theme)->toBe('Nouveau thème')
        ->and($fresh->target_audience)->toBe('Référents qualité');
});

it('refuses to update an archived plan (service guard)', function (): void {
    $user = actingAsRole('dirigeant');
    $plan = TrainingPlan::factory()->forStructure($user->structure)->create([
        'status' => TrainingPlanStatus::Archived,
        'archived_at' => now(),
    ]);

    $this->put("/formations/{$plan->id}", [
        'year' => 2027,
        'theme' => 'Tentative interdite',
    ])->assertStatus(409);
});

it('lists the newly created plan on the formations index', function (): void {
    $user = actingAsRole('dirigeant');
    TrainingPlan::factory()->forStructure($user->structure)->create([
        'year' => 2027,
        'theme' => 'Plan réel — pas une démo',
    ]);

    $this->get('/formations')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('dashboard/formations/index')
            ->where('plans.0.theme', 'Plan réel — pas une démo'));
});
