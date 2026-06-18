<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AnnualReportStatus;
use App\Models\AnnualReport;
use App\Services\AnnualReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Async PDF generation for an annual quality report.
 *
 * Follows the same pattern as GenerateAuditRunPdfJob:
 *   - 3 tries, 60s backoff
 *   - On failure, marks the report as échoué so the dashboard shows
 *     a retry button rather than a spinner
 *   - dispatchAfterCommit so the job worker sees the committed row
 */
class GenerateAnnualReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public bool $dispatchAfterCommit = true;

    public function __construct(public readonly AnnualReport $report) {}

    public function handle(AnnualReportService $service): void
    {
        Log::info('Generating annual report', [
            'annual_report_id' => $this->report->id,
            'structure_id' => $this->report->structure_id,
            'year' => $this->report->year,
        ]);

        $service->generate($this->report->fresh());
    }

    public function failed(Throwable $e): void
    {
        Log::error('Annual report generation failed', [
            'annual_report_id' => $this->report->id,
            'error' => $e->getMessage(),
        ]);

        $this->report->update(['status' => AnnualReportStatus::Echoue->value]);
    }
}
