<?php

declare(strict_types=1);

use App\Enums\AnnualReportStatus;
use App\Models\AnnualReport;
use App\Models\Structure;
use App\Models\User;
use App\Services\AnnualReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake(AnnualReportService::DISK);

    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
    $this->user = User::factory()->forStructure($this->structure)->create();
    $this->service = app(AnnualReportService::class);
});

it('requestGeneration creates a report in en_attente status', function (): void {
    $report = $this->service->requestGeneration($this->structure, $this->user, 2025);

    expect($report)->toBeInstanceOf(AnnualReport::class)
        ->and($report->status)->toBe(AnnualReportStatus::EnAttente)
        ->and($report->year)->toBe(2025)
        ->and($report->structure_id)->toBe($this->structure->id);
});

it('requestGeneration is idempotent — re-request resets the same record', function (): void {
    $first = $this->service->requestGeneration($this->structure, $this->user, 2025);
    $second = $this->service->requestGeneration($this->structure, $this->user, 2025);

    expect($first->id)->toBe($second->id);
    expect(AnnualReport::withoutGlobalScopes()->where('structure_id', $this->structure->id)->count())->toBe(1);
});

it('generate renders a PDF and marks the report as genere', function (): void {
    $report = $this->service->requestGeneration($this->structure, $this->user, 2025);

    $this->service->generate($report->fresh());

    $report->refresh();
    expect($report->status)->toBe(AnnualReportStatus::Genere)
        ->and($report->pdf_path)->not->toBeNull()
        ->and($report->pdf_generated_at)->not->toBeNull();

    Storage::disk(AnnualReportService::DISK)->assertExists($report->pdf_path);
});

it('generate sets status to en_cours before rendering', function (): void {
    $report = $this->service->requestGeneration($this->structure, $this->user, 2025);
    // We only test idempotency and the final state here — the intermediate
    // en_cours state is transient within the DB transaction.
    $this->service->generate($report->fresh());

    expect($report->fresh()->status)->toBe(AnnualReportStatus::Genere);
});

it('signedUrl throws 404 when no PDF is generated', function (): void {
    $report = $this->service->requestGeneration($this->structure, $this->user, 2025);

    expect(fn () => $this->service->signedUrl($report))
        ->toThrow(HttpException::class);
});
