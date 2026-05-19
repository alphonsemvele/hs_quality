<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AnnualReportStatus;
use App\Enums\AuditRunStatus;
use App\Models\AnnualReport;
use App\Models\AuditRun;
use App\Models\Certification;
use App\Models\Incident;
use App\Models\Intervention;
use App\Models\QvctResponse;
use App\Models\Structure;
use App\Models\TrainingAttendance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Phase 3 — Annual quality report PDF per structure.
 *
 * Collects KPIs for all six modules over the requested calendar year,
 * renders a Blade template through dompdf, uploads to S3 with SSE-KMS,
 * and persists the path. Access via a time-limited signed S3 URL.
 *
 * One report per structure per year (unique DB constraint). Calling
 * generate() when one already exists for that year re-generates and
 * overwrites (force-refresh path).
 */
class AnnualReportService
{
    public const DISK = 's3';

    public function requestGeneration(Structure $structure, User $requester, int $year): AnnualReport
    {
        $report = AnnualReport::withoutGlobalScopes()
            ->updateOrCreate(
                ['structure_id' => $structure->id, 'year' => $year],
                [
                    'status' => AnnualReportStatus::EnAttente->value,
                    'pdf_path' => null,
                    'pdf_generated_at' => null,
                    'requested_by_user_id' => $requester->id,
                ],
            );

        return $report;
    }

    /**
     * Collect all KPIs for the year and render the PDF.
     * Called by GenerateAnnualReportJob.
     */
    public function generate(AnnualReport $report): AnnualReport
    {
        $report->update(['status' => AnnualReportStatus::EnCours->value]);

        $data = $this->collectData($report->structure, $report->year);
        $pdf = $this->renderPdf($data);
        $path = "annual-reports/{$report->structure_id}/{$report->year}-".Str::uuid().'.pdf';

        Storage::disk(self::DISK)->put($path, $pdf, ['ServerSideEncryption' => 'aws:kms']);

        $report->forceFill([
            'status' => AnnualReportStatus::Genere->value,
            'pdf_path' => $path,
            'pdf_generated_at' => now(),
        ])->save();

        return $report;
    }

    public function signedUrl(AnnualReport $report, int $minutes = 60): string
    {
        if (! $report->hasPdf()) {
            throw new HttpException(404, 'Le rapport PDF n\'est pas encore disponible.');
        }

        return Storage::disk(self::DISK)->temporaryUrl($report->pdf_path, now()->addMinutes($minutes));
    }

    /** @return array<string, mixed> */
    private function collectData(Structure $structure, int $year): array
    {
        $start = "{$year}-01-01";
        $end = "{$year}-12-31";

        // M1 — Interventions
        $interventionStats = Intervention::withoutGlobalScopes()
            ->where('structure_id', $structure->id)
            ->whereBetween('planned_date', [$start, $end])
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        // M2 — Incidents
        $incidentStats = Incident::withoutGlobalScopes()
            ->where('structure_id', $structure->id)
            ->whereBetween('created_at', ["{$start} 00:00:00", "{$end} 23:59:59"])
            ->whereNull('deleted_at')
            ->selectRaw('gravite, COUNT(*) as cnt')
            ->groupBy('gravite')
            ->pluck('cnt', 'gravite')
            ->toArray();

        // M3 — QVCT average score
        $avgQvct = QvctResponse::withoutGlobalScopes()
            ->where('structure_id', $structure->id)
            ->whereBetween('created_at', ["{$start} 00:00:00", "{$end} 23:59:59"])
            ->whereNotNull('score')
            ->avg('score');

        // M5 — Training completions
        $trainingCompletions = TrainingAttendance::withoutGlobalScopes()
            ->where('structure_id', $structure->id)
            ->where('status', 'attended')
            ->whereBetween('created_at', ["{$start} 00:00:00", "{$end} 23:59:59"])
            ->count();

        $expiringCerts = Certification::withoutGlobalScopes()
            ->where('structure_id', $structure->id)
            ->whereBetween('expires_at', [$start, $end])
            ->count();

        // M6 — Audits
        $audits = AuditRun::withoutGlobalScopes()
            ->where('structure_id', $structure->id)
            ->where('status', AuditRunStatus::Finalised->value)
            ->whereBetween('run_date', [$start, $end])
            ->get(['score', 'max_score', 'title']);

        $avgConformite = $audits->count() > 0
            ? round($audits->avg(fn ($r) => $r->max_score > 0 ? $r->score / $r->max_score * 100 : 0), 1)
            : null;

        return compact(
            'structure',
            'year',
            'interventionStats',
            'incidentStats',
            'avgQvct',
            'trainingCompletions',
            'expiringCerts',
            'audits',
            'avgConformite',
        );
    }

    private function renderPdf(array $data): string
    {
        return Pdf::loadView('reports.annual', $data)
            ->setPaper('a4', 'portrait')
            ->output();
    }
}
