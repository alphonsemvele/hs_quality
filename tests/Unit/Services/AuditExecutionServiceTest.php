<?php

declare(strict_types=1);

use App\Enums\AuditRunStatus;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRunResponse;
use App\Models\Structure;
use App\Models\User;
use App\Services\AuditExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(AuditExecutionService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->finaliser = User::factory()->forStructure($this->structure)->create();
    $this->grid = AuditGrid::factory()->forStructure($this->structure)->create();
    $this->items = AuditGridItem::factory()->forGrid($this->grid)->count(3)
        ->state(['max_points' => 5])
        ->create();
});

it('starts a draft run', function (): void {
    $run = $this->service->start($this->grid, 'Évaluation Q3', '2026-09-15');

    expect($run->status)->toBe(AuditRunStatus::Draft)
        ->and($run->audit_grid_id)->toBe($this->grid->id)
        ->and($run->title)->toBe('Évaluation Q3');
});

it('transitions draft to in_progress on first response', function (): void {
    $run = $this->service->start($this->grid, 'Évaluation', '2026-09-15');

    $this->service->recordResponse($run, $this->items->first(), 5.0, null, null, null);

    expect($run->fresh()->status)->toBe(AuditRunStatus::InProgress);
});

it('overwrites a response when called twice for the same item', function (): void {
    $run = $this->service->start($this->grid, 'Évaluation', '2026-09-15');

    $this->service->recordResponse($run, $this->items->first(), 3.0, 'first attempt', null, null);
    $this->service->recordResponse($run, $this->items->first(), 4.0, 'corrected', null, null);

    $responses = AuditRunResponse::query()->where('audit_run_id', $run->id)->get();

    expect($responses)->toHaveCount(1);
    expect((float) $responses->first()->score)->toBe(4.0);
    expect($responses->first()->comment)->toBe('corrected');
});

it('rejects a response from a different grid\'s item', function (): void {
    $run = $this->service->start($this->grid, 'Évaluation', '2026-09-15');

    $otherGrid = AuditGrid::factory()->forStructure($this->structure)->create();
    $foreignItem = AuditGridItem::factory()->forGrid($otherGrid)->create();

    expect(fn () => $this->service->recordResponse($run, $foreignItem, 5.0, null, null, null))
        ->toThrow(HttpException::class);
});

it('finalises and freezes the score', function (): void {
    $run = $this->service->start($this->grid, 'Évaluation', '2026-09-15');

    foreach ($this->items as $item) {
        $this->service->recordResponse($run, $item, 4.0, null, null, null);
    }

    $finalised = $this->service->finalise($run, $this->finaliser);

    expect($finalised->status)->toBe(AuditRunStatus::Finalised)
        ->and((float) $finalised->score)->toBe(12.0)
        ->and((float) $finalised->max_score)->toBe(15.0)
        ->and($finalised->finalised_by)->toBe($this->finaliser->id);
});

it('cannot record a response after finalise', function (): void {
    $run = $this->service->start($this->grid, 'Évaluation', '2026-09-15');
    $this->service->recordResponse($run, $this->items->first(), 5.0, null, null, null);
    $this->service->finalise($run, $this->finaliser);

    expect(fn () => $this->service->recordResponse($run->fresh(), $this->items->last(), 3.0, null, null, null))
        ->toThrow(HttpException::class);
});

it('cannot finalise twice', function (): void {
    $run = $this->service->start($this->grid, 'Évaluation', '2026-09-15');
    $this->service->recordResponse($run, $this->items->first(), 5.0, null, null, null);
    $this->service->finalise($run, $this->finaliser);

    expect(fn () => $this->service->finalise($run->fresh(), $this->finaliser))
        ->toThrow(HttpException::class);
});

it('finalised score does not change if grid items are added later', function (): void {
    $run = $this->service->start($this->grid, 'Évaluation', '2026-09-15');
    foreach ($this->items as $item) {
        $this->service->recordResponse($run, $item, 5.0, null, null, null);
    }
    $finalised = $this->service->finalise($run, $this->finaliser);
    $frozenScore = (float) $finalised->score;
    $frozenMax = (float) $finalised->max_score;

    // Add a new item to the grid AFTER finalise.
    AuditGridItem::factory()->forGrid($this->grid)->create(['max_points' => 100]);

    expect((float) $run->fresh()->score)->toBe($frozenScore);
    expect((float) $run->fresh()->max_score)->toBe($frozenMax);
});
