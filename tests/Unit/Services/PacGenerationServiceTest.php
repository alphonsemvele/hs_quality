<?php

declare(strict_types=1);

use App\Enums\AuditRunStatus;
use App\Enums\PacActionStatus;
use App\Enums\PacStatus;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\PacAction;
use App\Models\Structure;
use App\Models\User;
use App\Services\PacGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(PacGenerationService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
    $this->author = User::factory()->forStructure($this->structure)->create();
});

function makeFinalisedRunWithResponses(Structure $structure, array $scores, int $maxPoints = 5): AuditRun
{
    $grid = AuditGrid::factory()->forStructure($structure)->create();
    $items = AuditGridItem::factory()->forGrid($grid)->count(count($scores))
        ->state(['max_points' => $maxPoints])
        ->create();

    $run = AuditRun::factory()->forGrid($grid)->finalised()->create();

    foreach ($items as $i => $item) {
        AuditRunResponse::factory()->forRunAndItem($run, $item)->create([
            'score' => $scores[$i],
        ]);
    }

    return $run->fresh();
}

it('refuses to generate from a non-finalised run', function (): void {
    $grid = AuditGrid::factory()->forStructure($this->structure)->create();
    $run = AuditRun::factory()->forGrid($grid)->state(['status' => AuditRunStatus::InProgress->value])->create();

    expect(fn () => $this->service->generateForRun($run, $this->author))
        ->toThrow(HttpException::class);
});

it('creates a draft PAC linked back to the audit run', function (): void {
    $run = makeFinalisedRunWithResponses($this->structure, [5, 5, 5]); // no gaps

    $pac = $this->service->generateForRun($run, $this->author);

    expect($pac->status)->toBe(PacStatus::Draft)
        ->and($pac->audit_run_id)->toBe($run->id)
        ->and($pac->created_by)->toBe($this->author->id);
});

it('emits one PacAction per gap (score < 50% × max_points)', function (): void {
    // Items: max_points=5; scores: 1 (gap), 4 (ok), 2 (gap), 5 (ok)
    $run = makeFinalisedRunWithResponses($this->structure, [1, 4, 2, 5]);

    $pac = $this->service->generateForRun($run, $this->author);

    expect($pac->actions()->count())->toBe(2);
});

it('is idempotent — re-running adds no duplicate actions', function (): void {
    $run = makeFinalisedRunWithResponses($this->structure, [1, 5, 2, 5]); // 2 gaps

    $first = $this->service->generateForRun($run, $this->author);
    $firstActionCount = $first->actions()->count();
    expect($firstActionCount)->toBe(2);

    $second = $this->service->generateForRun($run, $this->author);

    expect($second->id)->toBe($first->id);
    expect($second->actions()->count())->toBe(2);
});

it('preserves an operator-edited action across re-generations', function (): void {
    $run = makeFinalisedRunWithResponses($this->structure, [1, 5, 5]); // 1 gap
    $pac = $this->service->generateForRun($run, $this->author);

    $action = $pac->actions()->first();
    $action->update([
        'status' => PacActionStatus::InProgress->value,
        'responsible_user_id' => $this->author->id,
    ]);

    $this->service->generateForRun($run, $this->author);

    $reloaded = PacAction::query()->where('id', $action->id)->first();
    expect($reloaded->status)->toBe(PacActionStatus::InProgress)
        ->and($reloaded->responsible_user_id)->toBe($this->author->id);
});

it('does not count un-scored responses as gaps', function (): void {
    $grid = AuditGrid::factory()->forStructure($this->structure)->create();
    $items = AuditGridItem::factory()->forGrid($grid)->count(2)
        ->state(['max_points' => 5])
        ->create();
    $run = AuditRun::factory()->forGrid($grid)->finalised()->create();

    AuditRunResponse::factory()->forRunAndItem($run, $items[0])->create(['score' => null]);
    AuditRunResponse::factory()->forRunAndItem($run, $items[1])->create(['score' => 1]);

    $pac = $this->service->generateForRun($run, $this->author);

    expect($pac->actions()->count())->toBe(1);
});
