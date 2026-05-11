<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Qvct\UpdateQvctIndicatorRequest;
use App\Models\QvctIndicator;
use App\Services\IndicatorIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QvctIndicatorController extends Controller
{
    public function __construct(private readonly IndicatorIngestionService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QvctIndicator::class);

        return response()->json([
            'data' => QvctIndicator::query()
                ->orderByDesc('period_start')
                ->limit((int) $request->input('limit', 24))
                ->get(),
        ]);
    }

    public function update(UpdateQvctIndicatorRequest $request, QvctIndicator $indicator): JsonResponse
    {
        $indicator->update([
            ...$request->validated(),
            'captured_by' => $request->user()->id,
            'captured_at' => now(),
        ]);

        return response()->json($indicator->fresh());
    }

    /**
     * Trigger an immediate snapshot for the caller's tenant. Useful for
     * RH after they've launched/closed a campaign mid-period and want
     * to see the barometer mean reflected without waiting for the cron.
     */
    public function snapshot(Request $request): JsonResponse
    {
        $this->authorize('create', QvctIndicator::class);

        $indicator = $this->service->snapshotForStructure(
            $request->user()->structure,
        );

        return response()->json($indicator, 201);
    }
}
