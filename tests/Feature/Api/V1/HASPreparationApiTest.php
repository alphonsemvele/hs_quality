<?php

declare(strict_types=1);

use App\Enums\AuditGridSource;
use App\Enums\AuditRunStatus;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\Pac;
use App\Models\PacAction;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

function makeFinalisedHasRun(User $user): AuditRun
{
    $grid = AuditGrid::factory()->forStructure($user->structure)->create([
        'source' => AuditGridSource::Has->value,
    ]);

    $item = AuditGridItem::factory()->create([
        'structure_id' => $user->structure_id,
        'audit_grid_id' => $grid->id,
        'max_points' => 10,
        'position' => 1,
    ]);

    $run = AuditRun::factory()->create([
        'structure_id' => $user->structure_id,
        'audit_grid_id' => $grid->id,
        'status' => AuditRunStatus::Finalised,
        'score' => 8.0,
        'max_score' => 10.0,
        'finalised_by' => $user->id,
        'finalised_at' => now(),
    ]);

    AuditRunResponse::factory()->create([
        'structure_id' => $user->structure_id,
        'audit_run_id' => $run->id,
        'audit_grid_item_id' => $item->id,
        'score' => 8.0,
    ]);

    return $run;
}

it('returns 200 with gap analysis for a finalised HAS run', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $run = makeFinalisedHasRun($rq);

    $response = $this->getJson("/api/v1/audit-runs/{$run->id}/has-preparation");

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'run_id',
        'run_title',
        'grid_source',
        'overall_readiness_pct',
        'conformity_threshold_pct',
        'items' => [['item_id', 'title', 'score', 'max_points', 'conformity_pct', 'status', 'gap', 'pac_action_ids']],
        'summary' => ['conforme', 'a_ameliorer', 'non_conforme', 'total'],
    ]);

    expect($response->json('grid_source'))->toBe('has');
    expect($response->json('overall_readiness_pct'))->toEqual(80.0);
    expect($response->json('conformity_threshold_pct'))->toEqual(70.0);
    expect($response->json('summary.total'))->toBe(1);
});

it('returns 409 for a draft run', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $run = makeFinalisedHasRun($rq);
    $run->update(['status' => AuditRunStatus::Draft]);

    $this->getJson("/api/v1/audit-runs/{$run->id}/has-preparation")
        ->assertStatus(409);
});

it('returns 422 for a non-HAS grid', function (): void {
    $rq = actingAsApiRole('referent_qualite');

    $isoGrid = AuditGrid::factory()->forStructure($rq->structure)->create([
        'source' => AuditGridSource::Iso9001->value,
    ]);

    $item = AuditGridItem::factory()->create([
        'structure_id' => $rq->structure_id,
        'audit_grid_id' => $isoGrid->id,
        'max_points' => 5,
    ]);

    $run = AuditRun::factory()->create([
        'structure_id' => $rq->structure_id,
        'audit_grid_id' => $isoGrid->id,
        'status' => AuditRunStatus::Finalised,
        'score' => 3.0,
        'max_score' => 5.0,
        'finalised_by' => $rq->id,
        'finalised_at' => now(),
    ]);

    $this->getJson("/api/v1/audit-runs/{$run->id}/has-preparation")
        ->assertStatus(422);
});

it('blocks an intervenant — 403', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $run = makeFinalisedHasRun($rq);

    actingAsApiRole('intervenant', $rq->structure);

    $this->getJson("/api/v1/audit-runs/{$run->id}/has-preparation")
        ->assertForbidden();
});

it('returns 404 for a cross-tenant run', function (): void {
    $rqOther = actingAsApiRole('referent_qualite');
    $foreignRun = makeFinalisedHasRun($rqOther);

    actingAsApiRole('referent_qualite');

    $this->getJson("/api/v1/audit-runs/{$foreignRun->id}/has-preparation")
        ->assertNotFound();
});

it('items include pac_action_ids when linked PAC actions exist', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $run = makeFinalisedHasRun($rq);

    // Grab the response that was created in makeFinalisedHasRun.
    $auditResponse = $run->responses()->first();
    $pac = Pac::factory()->create(['structure_id' => $rq->structure_id]);
    $action = PacAction::factory()->create([
        'structure_id' => $rq->structure_id,
        'pac_id' => $pac->id,
        'source_audit_response_id' => $auditResponse->id,
    ]);

    $response = $this->getJson("/api/v1/audit-runs/{$run->id}/has-preparation");
    $response->assertSuccessful();

    $item = collect($response->json('items'))->first();
    expect($item['pac_action_ids'])->toContain($action->id);
});
