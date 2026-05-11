<?php

declare(strict_types=1);

use App\Enums\AuditRunStatus;
use App\Jobs\GenerateAuditRunPdfJob;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

function makeFinalisedRun(User $user): AuditRun
{
    $grid = AuditGrid::factory()->forStructure($user->structure)->create();
    $item = AuditGridItem::factory()->forGrid($grid)->create(['max_points' => 10]);

    $run = AuditRun::factory()->create([
        'structure_id' => $user->structure_id,
        'audit_grid_id' => $grid->id,
        'status' => AuditRunStatus::Finalised,
        'score' => 7.5,
        'max_score' => 10,
        'finalised_by' => $user->id,
        'finalised_at' => now(),
    ]);

    AuditRunResponse::factory()->create([
        'structure_id' => $user->structure_id,
        'audit_run_id' => $run->id,
        'audit_grid_item_id' => $item->id,
        'score' => 7.5,
    ]);

    return $run;
}

it('dispatches GenerateAuditRunPdfJob on POST /generate-pdf for a finalised run (202)', function (): void {
    Bus::fake([GenerateAuditRunPdfJob::class]);
    $rq = actingAsApiRole('referent_qualite');
    $run = makeFinalisedRun($rq);

    $response = $this->postJson("/api/v1/audit-runs/{$run->id}/generate-pdf");

    $response->assertStatus(202);
    Bus::assertDispatched(GenerateAuditRunPdfJob::class, fn ($job) => $job->auditRun->id === $run->id);
});

it('refuses PDF export on a draft run with 409', function (): void {
    Bus::fake([GenerateAuditRunPdfJob::class]);
    $rq = actingAsApiRole('referent_qualite');
    $run = makeFinalisedRun($rq);
    $run->update(['status' => AuditRunStatus::Draft]);

    $response = $this->postJson("/api/v1/audit-runs/{$run->id}/generate-pdf");

    $response->assertStatus(409);
    Bus::assertNotDispatched(GenerateAuditRunPdfJob::class);
});

it('returns 404 from /pdf-url when the run has no PDF yet', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $run = makeFinalisedRun($rq);

    $response = $this->getJson("/api/v1/audit-runs/{$run->id}/pdf-url");

    $response->assertNotFound();
});

it('returns a signed URL from /pdf-url after the job runs', function (): void {
    Storage::fake('s3');
    $rq = actingAsApiRole('referent_qualite');
    $run = makeFinalisedRun($rq);

    // Run the job synchronously (QUEUE_CONNECTION=sync in phpunit.xml).
    GenerateAuditRunPdfJob::dispatch($run);

    $run->refresh();
    expect($run->hasPdf())->toBeTrue();

    $response = $this->getJson("/api/v1/audit-runs/{$run->id}/pdf-url");

    $response->assertSuccessful();
    expect($response->json('expires_in_minutes'))->toBe(60);
    expect($response->json('url'))->toBeString();
});

it('blocks an intervenant from PDF export — 403 (no audits.view permission)', function (): void {
    $rq = actingAsApiRole('referent_qualite');
    $run = makeFinalisedRun($rq);

    actingAsApiRole('intervenant', $rq->structure);

    $this->postJson("/api/v1/audit-runs/{$run->id}/generate-pdf")
        ->assertForbidden();

    $this->getJson("/api/v1/audit-runs/{$run->id}/pdf-url")
        ->assertForbidden();
});

it('returns 404 for a cross-tenant run instead of leaking existence (RouteScope binding)', function (): void {
    $rqOther = actingAsApiRole('referent_qualite');
    $foreignRun = makeFinalisedRun($rqOther);

    actingAsApiRole('referent_qualite');

    $this->postJson("/api/v1/audit-runs/{$foreignRun->id}/generate-pdf")
        ->assertNotFound();
});
