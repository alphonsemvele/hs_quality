<?php

declare(strict_types=1);

use App\Enums\QvctActionPlanItemStatus;
use App\Enums\QvctActionPlanStatus;
use App\Models\QvctActionPlan;
use App\Models\QvctActionPlanItem;
use App\Models\Structure;
use App\Models\User;
use App\Services\ActionPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(ActionPlanService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->author = User::factory()->forStructure($this->structure)->create();
});

it('drafts an action plan with author + draft status', function (): void {
    $plan = $this->service->draft($this->author, [
        'title' => 'Plan Q3 2026',
        'target_quarter' => 'Q3-2026',
    ]);

    expect($plan->status)->toBe(QvctActionPlanStatus::Draft)
        ->and($plan->created_by)->toBe($this->author->id)
        ->and($plan->structure_id)->toBe($this->structure->id);
});

it('publishes a draft plan', function (): void {
    $plan = QvctActionPlan::factory()->forStructure($this->structure)->create();

    $published = $this->service->publish($plan, $this->author);

    expect($published->status)->toBe(QvctActionPlanStatus::Published)
        ->and($published->published_by)->toBe($this->author->id)
        ->and($published->published_at)->not->toBeNull();
});

it('publishing an already-published plan is a no-op', function (): void {
    $plan = QvctActionPlan::factory()->forStructure($this->structure)->published()->create();

    $republished = $this->service->publish($plan, $this->author);

    expect($republished->status)->toBe(QvctActionPlanStatus::Published);
});

it('cannot publish a closed plan', function (): void {
    $plan = QvctActionPlan::factory()->forStructure($this->structure)->closed()->create();

    expect(fn () => $this->service->publish($plan, $this->author))
        ->toThrow(HttpException::class);
});

it('cannot close a draft plan (must publish first)', function (): void {
    $plan = QvctActionPlan::factory()->forStructure($this->structure)->create();

    expect(fn () => $this->service->close($plan, $this->author))
        ->toThrow(HttpException::class);
});

it('closes a published plan', function (): void {
    $plan = QvctActionPlan::factory()->forStructure($this->structure)->published()->create();

    $closed = $this->service->close($plan, $this->author);

    expect($closed->status)->toBe(QvctActionPlanStatus::Closed)
        ->and($closed->closed_by)->toBe($this->author->id);
});

it('adds items to a draft plan', function (): void {
    $plan = QvctActionPlan::factory()->forStructure($this->structure)->create();

    $item = $this->service->addItem($plan, [
        'title' => 'Réduire la surcharge équipe Lyon',
        'impact_measurement_target' => 'Score surcharge moyen ≥ 3.5',
    ]);

    expect($item->action_plan_id)->toBe($plan->id)
        ->and($item->status)->toBe(QvctActionPlanItemStatus::Pending);
});

it('cannot add items to a published plan', function (): void {
    $plan = QvctActionPlan::factory()->forStructure($this->structure)->published()->create();

    expect(fn () => $this->service->addItem($plan, ['title' => 'Late item']))
        ->toThrow(HttpException::class);
});

it('updates item status while plan is published', function (): void {
    $plan = QvctActionPlan::factory()->forStructure($this->structure)->published()->create();
    $item = QvctActionPlanItem::factory()->forActionPlan($plan)->create();

    $updated = $this->service->updateItemStatus($item, QvctActionPlanItemStatus::InProgress->value);

    expect($updated->status)->toBe(QvctActionPlanItemStatus::InProgress);
});

it('cannot update item status on a closed plan', function (): void {
    $plan = QvctActionPlan::factory()->forStructure($this->structure)->closed()->create();
    $item = QvctActionPlanItem::factory()->forActionPlan($plan)->create();

    expect(fn () => $this->service->updateItemStatus($item, 'in_progress'))
        ->toThrow(HttpException::class);
});

it('records impact even after plan is closed (late figures allowed)', function (): void {
    $plan = QvctActionPlan::factory()->forStructure($this->structure)->closed()->create();
    $item = QvctActionPlanItem::factory()->forActionPlan($plan)->create();

    $measured = $this->service->recordImpact($item, 'Score surcharge atteint 3.7 — objectif tenu.');

    expect($measured->impact_measurement_actual)->toBe('Score surcharge atteint 3.7 — objectif tenu.')
        ->and($measured->impact_measured_at)->not->toBeNull();
});
