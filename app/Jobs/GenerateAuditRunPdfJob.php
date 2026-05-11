<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditRun;
use App\Services\AuditRunPdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Phase 2 / M6.20 — async PDF rendering for a finalised audit run.
 *
 * Idempotent: AuditRunPdfService::generate() short-circuits on a row
 * that already has a pdf_path. Safe to enqueue multiple times; safe
 * to retry on transient S3 failures (the unique storage path uses a
 * fresh UUID per generate-from-empty call, so a retry after success
 * would re-upload — but the short-circuit prevents that).
 *
 * Tenant context: AuditRun carries structure_id; the service reads
 * the structure relation directly without depending on the HTTP
 * tenant resolver.
 */
class GenerateAuditRunPdfJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly AuditRun $auditRun) {}

    public function handle(AuditRunPdfService $service): void
    {
        Log::info('Audit run PDF generation', [
            'audit_run_id' => $this->auditRun->id,
            'structure_id' => $this->auditRun->structure_id,
        ]);

        $service->generate($this->auditRun);
    }
}
