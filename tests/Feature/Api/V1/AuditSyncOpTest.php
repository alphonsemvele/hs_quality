<?php

declare(strict_types=1);

use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('records an audit response via the sync batch envelope', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $grid = AuditGrid::factory()->forStructure($rq->structure)->create();
    $item = AuditGridItem::factory()->forGrid($grid)->create(['max_points' => 5]);
    $run = AuditRun::factory()->forGrid($grid)->create();

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'audit.record_response',
            'resource_id' => $run->id,
            'payload' => [
                'audit_grid_item_id' => $item->id,
                'score' => 4,
                'comment' => 'Procédure documentée mais non affichée.',
                'evidence_url' => null,
            ],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('success');
    expect(AuditRunResponse::where('audit_run_id', $run->id)->count())->toBe(1);
});

it('rejects audit.record_response with missing run id', function (): void {
    actingAsApiRole('referent_qualite');

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'audit.record_response',
            'payload' => ['audit_grid_item_id' => (string) Str::uuid(), 'score' => 5],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('error');
});

it('rejects audit.record_response from a foreign-tenant run', function (): void {
    actingAsApiRole('referent_qualite');
    $foreignStructure = Structure::factory()->create();
    $foreignGrid = AuditGrid::factory()->forStructure($foreignStructure)->create();
    $foreignItem = AuditGridItem::factory()->forGrid($foreignGrid)->create();
    $foreignRun = AuditRun::factory()->forGrid($foreignGrid)->create();

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'audit.record_response',
            'resource_id' => $foreignRun->id,
            'payload' => ['audit_grid_item_id' => $foreignItem->id, 'score' => 5],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('rejected');
});

it('intervenant cannot record audit responses (lacks audits.execute)', function (): void {
    $intervenant = actingAsApiRole('intervenant');
    $grid = AuditGrid::factory()->forStructure($intervenant->structure)->create();
    $item = AuditGridItem::factory()->forGrid($grid)->create();
    $run = AuditRun::factory()->forGrid($grid)->create();

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'audit.record_response',
            'resource_id' => $run->id,
            'payload' => ['audit_grid_item_id' => $item->id, 'score' => 5],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('rejected');
});
