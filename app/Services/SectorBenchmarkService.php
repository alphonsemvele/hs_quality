<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SectorBenchmarkSnapshot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Orchestrates sector benchmark snapshot generation and retrieval.
 *
 * Snapshots are generated once per month (by GenerateSectorBenchmarkJob
 * or on-demand by a platform admin). They are cached in Redis so the
 * dashboard endpoint doesn't hit the cross-tenant queries on every request.
 *
 * The CrossTenantQueryService is instantiated per-caller so every access
 * is audited with the user who triggered the query.
 */
class SectorBenchmarkService
{
    private const CACHE_TTL = 3600; // 1 hour — snapshots change at most once per month

    /**
     * Generate (or refresh) the benchmark snapshot for the given month.
     * Idempotent: re-running for a month that already has a snapshot
     * overwrites it (force-refresh path for corrections).
     */
    public function generateSnapshot(Carbon $month, User $triggeredBy): SectorBenchmarkSnapshot
    {
        $queryService = new CrossTenantQueryService($triggeredBy);

        $monthStart = $month->copy()->startOfMonth()->toDateString();
        $monthsBack = 3;

        $snapshot = SectorBenchmarkSnapshot::updateOrCreate(
            ['snapshot_month' => $monthStart],
            [
                'interventions_data' => $queryService->sectorInterventionsBenchmark($monthsBack),
                'incidents_data' => $queryService->sectorIncidentsBenchmark($monthsBack),
                'qvct_data' => $queryService->sectorQvctBenchmark(),
                'conformity_data' => $queryService->sectorConformityBenchmark(),
                'generated_by_user_id' => $triggeredBy->id,
            ],
        );

        Cache::forget($this->cacheKey($monthStart));

        return $snapshot;
    }

    /**
     * Return the most recent snapshot, served from Redis cache.
     * Returns null when no snapshot has been generated yet.
     */
    public function latestSnapshot(): ?SectorBenchmarkSnapshot
    {
        $snapshot = SectorBenchmarkSnapshot::orderByDesc('snapshot_month')->first();

        if ($snapshot === null) {
            return null;
        }

        $key = $this->cacheKey($snapshot->snapshot_month->toDateString());

        return Cache::remember($key, self::CACHE_TTL, fn () => $snapshot);
    }

    /**
     * Return a specific month's snapshot (no cache — used by admin tooling).
     */
    public function snapshotForMonth(Carbon $month): ?SectorBenchmarkSnapshot
    {
        return SectorBenchmarkSnapshot::where(
            'snapshot_month',
            $month->copy()->startOfMonth()->toDateString(),
        )->first();
    }

    private function cacheKey(string $month): string
    {
        return "benchmark:snapshot:{$month}";
    }
}
