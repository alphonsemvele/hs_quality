<?php

declare(strict_types=1);

use App\Enums\AuditRunStatus;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\Structure;
use App\Models\User;
use App\Services\AuditRunPdfService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Storage::fake('s3');

    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->grid = AuditGrid::factory()->forStructure($this->structure)->create([
        'title' => 'Référentiel HAS — SAAD',
    ]);
    $this->item = AuditGridItem::factory()->create([
        'structure_id' => $this->structure->id,
        'audit_grid_id' => $this->grid->id,
        'title' => 'Continuité des soins',
        'max_points' => 10,
    ]);

    $finalisedBy = User::factory()->forStructure($this->structure)->create();

    $this->run = AuditRun::factory()->create([
        'structure_id' => $this->structure->id,
        'audit_grid_id' => $this->grid->id,
        'title' => 'Audit annuel 2026',
        'status' => AuditRunStatus::Finalised,
        'score' => 7.5,
        'max_score' => 10,
        'finalised_by' => $finalisedBy->id,
        'finalised_at' => now(),
    ]);

    AuditRunResponse::factory()->create([
        'structure_id' => $this->structure->id,
        'audit_run_id' => $this->run->id,
        'audit_grid_item_id' => $this->item->id,
        'score' => 7.5,
        'comment' => 'Bonne continuité observée sur l\'échantillon contrôlé.',
    ]);

    $this->service = app(AuditRunPdfService::class);
});

it('renders + uploads the PDF to S3 and persists pdf_path + pdf_generated_at on the run', function (): void {
    $this->service->generate($this->run);

    $this->run->refresh();

    expect($this->run->pdf_path)->toStartWith("structures/{$this->structure->id}/audits/{$this->run->id}/");
    expect($this->run->pdf_path)->toEndWith('.pdf');
    expect($this->run->pdf_generated_at)->not->toBeNull();

    Storage::disk('s3')->assertExists($this->run->pdf_path);

    // Loose sanity check on the rendered bytes — every PDF starts with %PDF.
    $bytes = Storage::disk('s3')->get($this->run->pdf_path);
    expect(substr($bytes, 0, 4))->toBe('%PDF');
});

it('is idempotent — a second generate() on a row that already has a PDF is a no-op', function (): void {
    $this->service->generate($this->run);
    $firstPath = $this->run->fresh()->pdf_path;
    $firstGeneratedAt = $this->run->fresh()->pdf_generated_at;

    $this->service->generate($this->run->fresh());

    $this->run->refresh();
    expect($this->run->pdf_path)->toBe($firstPath);
    expect($this->run->pdf_generated_at?->equalTo($firstGeneratedAt))->toBeTrue();
});

it('refuses to export a draft run with 409', function (): void {
    $this->run->update(['status' => AuditRunStatus::Draft]);

    expect(fn () => $this->service->generate($this->run))
        ->toThrow(HttpException::class);
});

it('signedUrl throws 404 when no PDF has been generated yet', function (): void {
    expect(fn () => $this->service->signedUrl($this->run))
        ->toThrow(HttpException::class);
});
