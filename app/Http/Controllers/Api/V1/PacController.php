<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PacStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Audits\UpdatePacActionRequest;
use App\Models\Pac;
use App\Models\PacAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PacController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Pac::class);

        return response()->json([
            'data' => Pac::query()
                ->with(['auditRun:id,title,run_date'])
                ->withCount('actions')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function show(Pac $pac): JsonResponse
    {
        $this->authorize('view', $pac);

        $pac->load(['actions.responsibleUser:id,first_name,last_name']);

        return response()->json($pac);
    }

    public function close(Request $request, Pac $pac): JsonResponse
    {
        $this->authorize('close', $pac);

        $pac->update([
            'status' => PacStatus::Closed->value,
            'closed_by' => $request->user()->id,
            'closed_at' => now(),
        ]);

        return response()->json($pac->fresh());
    }

    public function updateAction(UpdatePacActionRequest $request, PacAction $action): JsonResponse
    {
        $action->update($request->validated());

        return response()->json($action->fresh());
    }
}
