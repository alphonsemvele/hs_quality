<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditRun;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Phase 2 / M6.20 — PDF export of a finalised audit run.
 *
 * Renders the audit-run Blade template through dompdf, uploads the
 * resulting PDF to S3 with SSE-KMS, and persists the path +
 * generation timestamp on the AuditRun row. Retrieval is via a
 * temporary signed URL — same convention as InterventionMediaService
 * (M2 W3) so the frontend pattern stays consistent.
 *
 * Idempotency: `generate()` checks `hasPdf()` first. A second call on
 * an already-generated run is a no-op (no re-render, no extra S3
 * write). To force regeneration after a finalised run is amended,
 * clear `pdf_path` + `pdf_generated_at` first.
 */
class AuditRunPdfService
{
    public const DISK = 's3';

    public function generate(AuditRun $run): AuditRun
    {
        if (! $run->isFinalised()) {
            throw new HttpException(409, "L'export PDF n'est disponible que pour un audit finalisé.");
        }

        if ($run->hasPdf()) {
            return $run;
        }

        $pdf = $this->renderPdf($run);
        $path = $this->storagePath($run);

        Storage::disk(self::DISK)->put($path, $pdf, [
            'ServerSideEncryption' => 'aws:kms',
        ]);

        $run->forceFill([
            'pdf_path' => $path,
            'pdf_generated_at' => now(),
        ])->save();

        return $run;
    }

    public function signedUrl(AuditRun $run, int $minutes = 60): string
    {
        if (! $run->hasPdf()) {
            throw new HttpException(404, "Aucun PDF n'est encore disponible pour cet audit.");
        }

        return Storage::disk(self::DISK)->temporaryUrl(
            $run->pdf_path,
            now()->addMinutes($minutes),
        );
    }

    private function renderPdf(AuditRun $run): string
    {
        $run->loadMissing(['grid', 'responses.item']);

        $structure = $run->structure;
        $finalisedBy = $run->finalised_by !== null
            ? User::query()->find($run->finalised_by)
            : null;

        $pdf = Pdf::loadView('pdf.audit-run', [
            'run' => $run,
            'structure' => $structure,
            'grid' => $run->grid,
            'responses' => $run->responses,
            'finalisedBy' => $finalisedBy,
            'generatedAt' => now(),
        ]);

        return $pdf->output();
    }

    private function storagePath(AuditRun $run): string
    {
        return sprintf(
            'structures/%s/audits/%s/%s.pdf',
            $run->structure_id,
            $run->id,
            (string) Str::uuid(),
        );
    }
}
