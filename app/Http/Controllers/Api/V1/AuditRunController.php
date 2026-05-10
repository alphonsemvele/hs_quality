<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Audits\RecordAuditResponseRequest;
use App\Http\Requests\Audits\StartAuditRunRequest;
use App\Jobs\GenerateAuditRunPdfJob;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\Pac;
use App\Services\AuditExecutionService;
use App\Services\AuditRunPdfService;
use App\Services\HASPreparationService;
use App\Services\PacGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuditRunController extends Controller
{
    public function __construct(
        private readonly AuditExecutionService $execution,
        private readonly PacGenerationService $pacGeneration,
        private readonly AuditRunPdfService $pdfService,
        private readonly HASPreparationService $hasPreparation,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', AuditRun::class);

        return response()->json([
            'data' => AuditRun::query()
                ->with(['grid:id,title,source'])
                ->orderByDesc('run_date')
                ->limit(50)
                ->get(),
        ]);
    }

    public function show(AuditRun $auditRun): JsonResponse
    {
        $this->authorize('view', $auditRun);

        $auditRun->load(['grid.items', 'responses.item:id,title,max_points']);

        return response()->json($auditRun);
    }

    public function store(StartAuditRunRequest $request): JsonResponse
    {
        $grid = AuditGrid::query()->findOrFail($request->validated('audit_grid_id'));
        $this->authorize('view', $grid);

        $run = $this->execution->start(
            $grid,
            $request->validated('title'),
            $request->validated('run_date'),
        );

        return response()->json($run, 201);
    }

    public function recordResponse(RecordAuditResponseRequest $request, AuditRun $auditRun): JsonResponse
    {
        $item = AuditGridItem::query()->findOrFail($request->validated('audit_grid_item_id'));

        $response = $this->execution->recordResponse(
            $auditRun,
            $item,
            $request->validated('score') !== null ? (float) $request->validated('score') : null,
            $request->validated('comment'),
            $request->validated('evidence_url'),
            $request->user(),
        );

        return response()->json($response, 201);
    }

    public function finalise(Request $request, AuditRun $auditRun): JsonResponse
    {
        $this->authorize('finalise', $auditRun);

        return response()->json($this->execution->finalise($auditRun, $request->user()));
    }

    public function generatePac(Request $request, AuditRun $auditRun): JsonResponse
    {
        $this->authorize('view', $auditRun);
        $this->authorize('create', Pac::class);

        return response()->json($this->pacGeneration->generateForRun($auditRun, $request->user()), 201);
    }

    /**
     * Phase 2 / M6.19 — HAS preparation guide gap analysis.
     *
     * Returns a prioritised per-item conformity report for the given
     * finalised HAS audit run. Non-conforme items appear first; items
     * with linked PAC actions include their IDs for cross-linking.
     *
     * 409 if the run is not finalised; 422 if the grid is not HAS.
     */
    public function hasPreparation(AuditRun $auditRun): JsonResponse
    {
        $this->authorize('view', $auditRun);

        return response()->json($this->hasPreparation->analyse($auditRun));
    }

    /**
     * Phase 2 / M6.20 — kick off PDF export.
     *
     * Returns 202 with the audit-run after enqueuing the render job.
     * Re-calling on a row that already has a PDF is a no-op (the job
     * short-circuits in the service); callers can poll {@see pdfUrl()}.
     */
    public function generatePdf(AuditRun $auditRun): JsonResponse
    {
        $this->authorize('view', $auditRun);

        if (! $auditRun->isFinalised()) {
            throw new HttpException(409, "L'export PDF n'est disponible que pour un audit finalisé.");
        }

        GenerateAuditRunPdfJob::dispatch($auditRun);

        return response()->json($auditRun, 202);
    }

    /**
     * Phase 2 / M6.20 — return a temporary signed S3 URL to the PDF.
     *
     * 404 (with French message) if the PDF has not yet been rendered.
     */
    public function pdfUrl(AuditRun $auditRun): JsonResponse
    {
        $this->authorize('view', $auditRun);

        return response()->json([
            'url' => $this->pdfService->signedUrl($auditRun),
            'expires_in_minutes' => 60,
        ]);
    }
}
