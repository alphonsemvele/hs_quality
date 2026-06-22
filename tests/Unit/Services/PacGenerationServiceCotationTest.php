<?php

declare(strict_types=1);

use App\Enums\AuditItemScale;
use App\Enums\AuditRunStatus;
use App\Enums\ExigenceLevel;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\Structure;
use App\Models\User;
use App\Services\PacGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(PacGenerationService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
    $this->user = User::factory()->create(['structure_id' => $this->structure->id]);
});

function finalisedHasRun(Structure $structure): array
{
    $grid = AuditGrid::factory()->forStructure($structure)->create();
    $run = AuditRun::factory()->forGrid($grid)->create([
        'status' => AuditRunStatus::Finalised->value,
        'finalised_at' => now(),
        'score' => 0,
        'max_score' => 0,
    ]);

    return [$grid, $run];
}

it('creates PAC actions only for C and D cotations', function (): void {
    [$grid, $run] = finalisedHasRun($this->structure);

    // Build 4 HAS-cotation items, one for each cotation.
    $itemA = AuditGridItem::factory()->forGrid($grid)->create(['scale' => AuditItemScale::HasCotation->value, 'max_points' => 4]);
    $itemB = AuditGridItem::factory()->forGrid($grid)->create(['scale' => AuditItemScale::HasCotation->value, 'max_points' => 4]);
    $itemC = AuditGridItem::factory()->forGrid($grid)->create(['scale' => AuditItemScale::HasCotation->value, 'max_points' => 4]);
    $itemD = AuditGridItem::factory()->forGrid($grid)->create(['scale' => AuditItemScale::HasCotation->value, 'max_points' => 4]);
    $itemNA = AuditGridItem::factory()->forGrid($grid)->create(['scale' => AuditItemScale::HasCotation->value, 'max_points' => 4]);

    AuditRunResponse::factory()->forRunAndItem($run, $itemA)->create(['cotation' => 'A', 'score' => 4]);
    AuditRunResponse::factory()->forRunAndItem($run, $itemB)->create(['cotation' => 'B', 'score' => 3]);
    AuditRunResponse::factory()->forRunAndItem($run, $itemC)->create(['cotation' => 'C', 'score' => 2]);
    AuditRunResponse::factory()->forRunAndItem($run, $itemD)->create(['cotation' => 'D', 'score' => 1]);
    AuditRunResponse::factory()->forRunAndItem($run, $itemNA)->create(['cotation' => 'NA', 'score' => null]);

    $pac = $this->service->generateForRun($run, $this->user);

    expect($pac->actions()->count())->toBe(2)
        ->and($pac->actions()->where('priority', 'moyenne')->count())->toBe(1)
        ->and($pac->actions()->where('priority', 'elevee')->count())->toBe(1);
});

it('marks Imperatif HAS gaps as CRITIQUE priority', function (): void {
    [$grid, $run] = finalisedHasRun($this->structure);

    $imperatifD = AuditGridItem::factory()->forGrid($grid)->create([
        'scale' => AuditItemScale::HasCotation->value,
        'max_points' => 4,
        'level' => ExigenceLevel::Imperatif->value,
    ]);
    $imperatifC = AuditGridItem::factory()->forGrid($grid)->create([
        'scale' => AuditItemScale::HasCotation->value,
        'max_points' => 4,
        'level' => ExigenceLevel::Imperatif->value,
    ]);
    $standardD = AuditGridItem::factory()->forGrid($grid)->create([
        'scale' => AuditItemScale::HasCotation->value,
        'max_points' => 4,
        'level' => ExigenceLevel::Standard->value,
    ]);

    AuditRunResponse::factory()->forRunAndItem($run, $imperatifD)->create(['cotation' => 'D', 'score' => 1]);
    AuditRunResponse::factory()->forRunAndItem($run, $imperatifC)->create(['cotation' => 'C', 'score' => 2]);
    AuditRunResponse::factory()->forRunAndItem($run, $standardD)->create(['cotation' => 'D', 'score' => 1]);

    $pac = $this->service->generateForRun($run, $this->user);

    expect($pac->actions()->where('priority', 'critique')->count())->toBe(2)
        ->and($pac->actions()->where('priority', 'elevee')->count())->toBe(1);
});

it('preserves legacy gap logic for non-HasCotation scales', function (): void {
    [$grid, $run] = finalisedHasRun($this->structure);

    $binary = AuditGridItem::factory()->forGrid($grid)->create([
        'scale' => AuditItemScale::Binary->value,
        'max_points' => 1,
    ]);
    AuditRunResponse::factory()->forRunAndItem($run, $binary)->create(['score' => 0, 'cotation' => null]);

    $pac = $this->service->generateForRun($run, $this->user);

    // 0 < 0.5 × 1 → gap, action created with default normale priority.
    expect($pac->actions()->count())->toBe(1)
        ->and($pac->actions()->first()->priority)->toBe('normale');
});
