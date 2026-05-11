<?php

declare(strict_types=1);

use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\Pac;
use App\Models\PacAction;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

// ── Audit run lifecycle ───────────────────────────────────────────────────────

it('referent_qualite can start an audit run via API', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $grid = AuditGrid::factory()->forStructure($rq->structure)->create();

    $response = $this->postJson('/api/v1/audit-runs', [
        'audit_grid_id' => $grid->id,
        'title' => 'Évaluation interne Q3 2026',
        'run_date' => '2026-09-15',
    ]);

    $response->assertCreated();
    expect(AuditRun::where('audit_grid_id', $grid->id)->count())->toBe(1);
});

it('referent_qualite can record + finalise + generate PAC end-to-end', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $grid = AuditGrid::factory()->forStructure($rq->structure)->create();
    $items = AuditGridItem::factory()->forGrid($grid)->count(3)
        ->state(['max_points' => 5])
        ->create();

    $startResp = $this->postJson('/api/v1/audit-runs', [
        'audit_grid_id' => $grid->id,
        'title' => 'Test',
        'run_date' => '2026-09-15',
    ]);
    $runId = $startResp->json('id');

    // Record 3 responses: 2 gaps (< 50% × 5), 1 ok
    foreach ([1, 4, 1] as $i => $score) {
        $this->postJson("/api/v1/audit-runs/{$runId}/responses", [
            'audit_grid_item_id' => $items[$i]->id,
            'score' => $score,
        ])->assertCreated();
    }

    $finaliseResp = $this->postJson("/api/v1/audit-runs/{$runId}/finalise");
    $finaliseResp->assertSuccessful();
    expect($finaliseResp->json('status'))->toBe('finalised');

    $pacResp = $this->postJson("/api/v1/audit-runs/{$runId}/generate-pac");
    $pacResp->assertCreated();
    $pacId = $pacResp->json('id');
    expect(PacAction::where('pac_id', $pacId)->count())->toBe(2); // 2 gaps
});

it('intervenant cannot start an audit run', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $grid = AuditGrid::factory()->forStructure($intervenant->structure)->create();

    $this->postJson('/api/v1/audit-runs', [
        'audit_grid_id' => $grid->id,
        'title' => 'Forbidden',
        'run_date' => '2026-09-15',
    ])->assertForbidden();
});

it('rejects record on a finalised run with 409', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $grid = AuditGrid::factory()->forStructure($rq->structure)->create();
    $item = AuditGridItem::factory()->forGrid($grid)->create();
    $run = AuditRun::factory()->forGrid($grid)->finalised()->create();

    $this->postJson("/api/v1/audit-runs/{$run->id}/responses", [
        'audit_grid_item_id' => $item->id,
        'score' => 5,
    ])->assertStatus(409);
});

// ── PAC ───────────────────────────────────────────────────────────────────────

it('referent_qualite can list PACs', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    Pac::factory()->forStructure($rq->structure)->count(3)->create();

    $response = $this->getJson('/api/v1/pacs');
    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(3);
});

it('referent_qualite can update a PAC action status', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $pac = Pac::factory()->forStructure($rq->structure)->create();
    $action = PacAction::factory()->forPac($pac)->create();

    $response = $this->putJson("/api/v1/pac-actions/{$action->id}", [
        'status' => 'in_progress',
    ]);

    $response->assertSuccessful();
    expect($action->fresh()->status->value)->toBe('in_progress');
});

it('referent_qualite can close a PAC', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $pac = Pac::factory()->forStructure($rq->structure)->create();

    $response = $this->postJson("/api/v1/pacs/{$pac->id}/close");
    $response->assertSuccessful();
    expect($response->json('status'))->toBe('closed');
});
