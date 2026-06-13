<?php

declare(strict_types=1);

use App\Enums\AuditItemScale;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRunResponse;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('accepts a HAS cotation and derives the numeric score', function (string $code, float $expectedScore): void {
    $rq = actingAsApiRole('referent_qualite');
    $grid = AuditGrid::factory()->forStructure($rq->structure)->create();
    $item = AuditGridItem::factory()->forGrid($grid)->create([
        'scale' => AuditItemScale::HasCotation->value,
        'max_points' => 4,
    ]);

    $startResp = $this->postJson('/api/v1/audit-runs', [
        'audit_grid_id' => $grid->id,
        'title' => 'Test cotation '.$code,
        'run_date' => '2026-09-15',
    ]);
    $runId = $startResp->json('id');

    $this->postJson("/api/v1/audit-runs/{$runId}/responses", [
        'audit_grid_item_id' => $item->id,
        'cotation' => $code,
    ])->assertCreated();

    $response = AuditRunResponse::withoutGlobalScopes()
        ->where('audit_run_id', $runId)
        ->where('audit_grid_item_id', $item->id)
        ->first();

    expect((string) $response->cotation)->toBe($code)
        ->and((float) $response->score)->toBe($expectedScore);
})->with([
    ['A', 4.0],
    ['B', 3.0],
    ['C', 2.0],
    ['D', 1.0],
]);

it('persists cotation NA with a null score for exclusion from scoring', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $grid = AuditGrid::factory()->forStructure($rq->structure)->create();
    $item = AuditGridItem::factory()->forGrid($grid)->create([
        'scale' => AuditItemScale::HasCotation->value,
        'max_points' => 4,
    ]);

    $startResp = $this->postJson('/api/v1/audit-runs', [
        'audit_grid_id' => $grid->id,
        'title' => 'Test NA',
        'run_date' => '2026-09-15',
    ]);
    $runId = $startResp->json('id');

    $this->postJson("/api/v1/audit-runs/{$runId}/responses", [
        'audit_grid_item_id' => $item->id,
        'cotation' => 'NA',
    ])->assertCreated();

    $response = AuditRunResponse::withoutGlobalScopes()
        ->where('audit_run_id', $runId)
        ->where('audit_grid_item_id', $item->id)
        ->first();

    expect((string) $response->cotation)->toBe('NA')
        ->and($response->score)->toBeNull();
});

it('rejects an invalid cotation value with 422', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $grid = AuditGrid::factory()->forStructure($rq->structure)->create();
    $item = AuditGridItem::factory()->forGrid($grid)->create([
        'scale' => AuditItemScale::HasCotation->value,
        'max_points' => 4,
    ]);

    $startResp = $this->postJson('/api/v1/audit-runs', [
        'audit_grid_id' => $grid->id,
        'title' => 'Test invalid',
        'run_date' => '2026-09-15',
    ]);
    $runId = $startResp->json('id');

    $this->postJson("/api/v1/audit-runs/{$runId}/responses", [
        'audit_grid_item_id' => $item->id,
        'cotation' => 'Z',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['cotation']);
});

it('requires cotation on HAS-cotation items', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $grid = AuditGrid::factory()->forStructure($rq->structure)->create();
    $item = AuditGridItem::factory()->forGrid($grid)->create([
        'scale' => AuditItemScale::HasCotation->value,
        'max_points' => 4,
    ]);

    $startResp = $this->postJson('/api/v1/audit-runs', [
        'audit_grid_id' => $grid->id,
        'title' => 'Test missing',
        'run_date' => '2026-09-15',
    ]);
    $runId = $startResp->json('id');

    // Submitting a bare score on a HAS-cotation item must fail.
    $this->postJson("/api/v1/audit-runs/{$runId}/responses", [
        'audit_grid_item_id' => $item->id,
        'score' => 3,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['cotation']);
});

it('forbids cotation on non-HAS scales', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $grid = AuditGrid::factory()->forStructure($rq->structure)->create();
    $item = AuditGridItem::factory()->forGrid($grid)->create([
        'scale' => AuditItemScale::Binary->value,
        'max_points' => 1,
    ]);

    $startResp = $this->postJson('/api/v1/audit-runs', [
        'audit_grid_id' => $grid->id,
        'title' => 'Test cross-scale',
        'run_date' => '2026-09-15',
    ]);
    $runId = $startResp->json('id');

    $this->postJson("/api/v1/audit-runs/{$runId}/responses", [
        'audit_grid_item_id' => $item->id,
        'cotation' => 'A',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['cotation']);
});
