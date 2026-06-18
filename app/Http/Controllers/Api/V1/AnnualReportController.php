<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequestAnnualReportRequest;
use App\Jobs\GenerateAnnualReportJob;
use App\Models\AnnualReport;
use App\Services\AnnualReportService;
use Illuminate\Http\JsonResponse;

class AnnualReportController extends Controller
{
    public function __construct(private readonly AnnualReportService $reportService) {}

    /** List all annual reports for the authenticated structure. */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', AnnualReport::class);

        return response()->json([
            'data' => AnnualReport::query()
                ->orderByDesc('year')
                ->get(['id', 'year', 'status', 'pdf_generated_at', 'created_at']),
        ]);
    }

    /**
     * Request generation of an annual report.
     * If a report for that year already exists it is reset and regenerated.
     */
    public function store(RequestAnnualReportRequest $request): JsonResponse
    {
        $report = $this->reportService->requestGeneration(
            currentStructure(),
            $request->user(),
            (int) $request->validated('year'),
        );

        GenerateAnnualReportJob::dispatch($report);

        return response()->json($report, 201);
    }

    /** Return a time-limited signed URL to download the PDF. */
    public function pdfUrl(AnnualReport $annualReport): JsonResponse
    {
        $this->authorize('view', $annualReport);

        return response()->json([
            'url' => $this->reportService->signedUrl($annualReport),
            'expires_in' => 3600,
        ]);
    }
}
