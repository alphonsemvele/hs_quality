<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Incident;
use App\Models\Structure;
use App\Models\User;
// StructureScope is applied by Eloquent models, not by DB::table().
// Raw query builder bypasses global scopes automatically.
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * The ONLY approved escape hatch for queries that intentionally cross tenants.
 * Used by Module 9 (benchmark anonymisé) and rare super-admin operations.
 *
 * Every method:
 *   1. Asserts the caller holds 'cross_tenant_benchmark.read' permission.
 *   2. Logs an audit entry BEFORE executing the query.
 *   3. Bypasses StructureScope via withoutGlobalScope().
 *   4. Returns aggregated / anonymized data ONLY — never individual records
 *      and never structure-identifying fields.
 *
 * Buckets are keyed by [type_structure, tier] — no structure_id in output.
 *
 * Using withoutGlobalScope(StructureScope::class) ANYWHERE outside this
 * service is a red-flag violation per CLAUDE.md anti-patterns.
 */
class CrossTenantQueryService
{
    private const REQUIRED_PERMISSION = 'cross_tenant_benchmark.read';

    public function __construct(private readonly User $caller)
    {
        if (! $this->caller->hasPermissionTo(self::REQUIRED_PERMISSION)) {
            throw new HttpException(403, 'Permission cross_tenant_benchmark.read requise.');
        }
    }

    /**
     * Sector benchmark: average monthly interventions per structure,
     * grouped by structure type + tier.
     *
     * Returns rows: { type_structure, tier, nb_structures, avg_interventions_par_mois }
     */
    public function sectorInterventionsBenchmark(int $monthsBack = 3): array
    {
        $this->auditLog('sectorInterventionsBenchmark', ['months_back' => $monthsBack]);

        $since = now()->subMonths($monthsBack)->startOfMonth();

        return DB::table('interventions')
            // DB::table() bypasses Eloquent global scopes automatically
            ->join('structures', 'structures.id', '=', 'interventions.structure_id')
            ->where('interventions.planned_date', '>=', $since)
            ->whereNull('interventions.deleted_at')
            ->select([
                'structures.type as type_structure',
                'structures.tier',
                DB::raw('COUNT(DISTINCT interventions.structure_id) as nb_structures'),
                DB::raw("ROUND(CAST(COUNT(interventions.id) AS REAL) / MAX(1, COUNT(DISTINCT interventions.structure_id)) / {$monthsBack}, 1) as avg_interventions_par_mois"),
            ])
            ->groupBy('structures.type', 'structures.tier')
            ->orderBy('structures.type')
            ->orderBy('structures.tier')
            ->get()
            ->toArray();
    }

    /**
     * Sector benchmark: average incident declaration rate per structure.
     *
     * Returns rows: { type_structure, tier, nb_structures, avg_incidents_par_mois }
     */
    public function sectorIncidentsBenchmark(int $monthsBack = 3): array
    {
        $this->auditLog('sectorIncidentsBenchmark', ['months_back' => $monthsBack]);

        $since = now()->subMonths($monthsBack)->startOfMonth();

        return DB::table('incidents')
            // DB::table() bypasses Eloquent global scopes automatically
            ->join('structures', 'structures.id', '=', 'incidents.structure_id')
            ->where('incidents.created_at', '>=', $since)
            ->whereNull('incidents.deleted_at')
            ->select([
                'structures.type as type_structure',
                'structures.tier',
                DB::raw('COUNT(DISTINCT incidents.structure_id) as nb_structures'),
                DB::raw("ROUND(CAST(COUNT(incidents.id) AS REAL) / MAX(1, COUNT(DISTINCT incidents.structure_id)) / {$monthsBack}, 2) as avg_incidents_par_mois"),
            ])
            ->groupBy('structures.type', 'structures.tier')
            ->orderBy('structures.type')
            ->orderBy('structures.tier')
            ->get()
            ->toArray();
    }

    /**
     * Sector benchmark: average QVCT campaign response score per structure.
     *
     * Returns rows: { type_structure, tier, nb_structures, avg_score_qvct }
     */
    public function sectorQvctBenchmark(): array
    {
        $this->auditLog('sectorQvctBenchmark', []);

        return DB::table('qvct_reponses')
            // DB::table() bypasses Eloquent global scopes automatically
            ->join('structures', 'structures.id', '=', 'qvct_reponses.structure_id')
            ->whereNotNull('qvct_reponses.score')
            ->select([
                'structures.type as type_structure',
                'structures.tier',
                DB::raw('COUNT(DISTINCT qvct_reponses.structure_id) as nb_structures'),
                DB::raw('ROUND(CAST(AVG(qvct_reponses.score) AS REAL), 2) as avg_score_qvct'),
            ])
            ->groupBy('structures.type', 'structures.tier')
            ->orderBy('structures.type')
            ->orderBy('structures.tier')
            ->get()
            ->toArray();
    }

    /**
     * Sector benchmark: average HAS audit conformity score per structure.
     *
     * Returns rows: { type_structure, tier, nb_structures, avg_conformite_pct }
     */
    public function sectorConformityBenchmark(): array
    {
        $this->auditLog('sectorConformityBenchmark', []);

        return DB::table('audit_runs')
            // DB::table() bypasses Eloquent global scopes automatically
            ->join('structures', 'structures.id', '=', 'audit_runs.structure_id')
            ->where('audit_runs.status', 'finalised')
            ->whereNotNull('audit_runs.score')
            ->whereNotNull('audit_runs.max_score')
            ->where('audit_runs.max_score', '>', 0)
            ->select([
                'structures.type as type_structure',
                'structures.tier',
                DB::raw('COUNT(DISTINCT audit_runs.structure_id) as nb_structures'),
                DB::raw('ROUND(CAST(AVG(audit_runs.score / audit_runs.max_score * 100) AS REAL), 1) as avg_conformite_pct'),
            ])
            ->groupBy('structures.type', 'structures.tier')
            ->orderBy('structures.type')
            ->orderBy('structures.tier')
            ->get()
            ->toArray();
    }

    private function auditLog(string $method, array $params): void
    {
        Log::info('CrossTenantQueryService', [
            'method' => $method,
            'params' => $params,
            'caller_id' => $this->caller->id,
            'tags' => ['cross_tenant', 'benchmark'],
        ]);
    }
}
