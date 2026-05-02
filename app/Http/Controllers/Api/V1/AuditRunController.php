<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Audits\RecordAuditResponseRequest;
use App\Http\Requests\Audits\StartAuditRunRequest;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\Pac;
use App\Services\AuditExecutionService;
use App\Services\PacGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditRunController extends Controller
{
    public function __construct(
        private readonly AuditExecutionService $execution,
        private readonly PacGenerationService $pacGeneration,
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
}
