<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateSectorBenchmarkJob;
use App\Models\SectorBenchmarkSnapshot;
use App\Services\SectorBenchmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sector benchmark endpoints — accessible only to users with the
 * 'cross_tenant_benchmark.read' permission (dirigeants and platform admins
 * per RoleSeeder).
 */
class SectorBenchmarkController extends Controller
{
    public function __construct(private readonly SectorBenchmarkService $benchmarkService) {}

    /**
     * Return the latest benchmark snapshot.
     * Returns 404 when no snapshot exists yet.
     */
    public function show(): JsonResponse
    {
        $this->authorize('viewAny', SectorBenchmarkSnapshot::class);

        $snapshot = $this->benchmarkService->latestSnapshot();

        if ($snapshot === null) {
            return response()->json(['message' => 'Aucun benchmark disponible. Lancez benchmark:generate.'], 404);
        }

        return response()->json([
            'snapshot_month' => $snapshot->snapshot_month->format('Y-m'),
            'generated_at' => $snapshot->updated_at,
            'interventions' => $snapshot->interventions_data,
            'incidents' => $snapshot->incidents_data,
            'qvct' => $snapshot->qvct_data,
            'conformity' => $snapshot->conformity_data,
        ]);
    }

    /**
     * Trigger an on-demand snapshot for the current month.
     * Restricted to platform admins via the Policy.
     */
    public function generate(Request $request): JsonResponse
    {
        $this->authorize('create', SectorBenchmarkSnapshot::class);

        GenerateSectorBenchmarkJob::dispatch(now()->startOfMonth(), $request->user());

        return response()->json(['message' => 'Génération du benchmark en cours.'], 202);
    }
}
