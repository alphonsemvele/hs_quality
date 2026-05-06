<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GraviteIncident;
use App\Enums\InterventionStatus;
use App\Enums\StatutIncident;
use App\Enums\StructureStatus;
use App\Enums\StructureTier;
use App\Enums\UserType;
use App\Models\Beneficiary;
use App\Models\Incident;
use App\Models\Intervention;
use App\Models\Structure;
use App\Models\User;
use App\Scopes\StructureScope;
use Illuminate\Support\Carbon;

/**
 * Platform-operator stats. The ONLY service authorised to aggregate data
 * across every tenant for the super_admin dashboard.
 *
 * Every query bypasses the StructureScope global scope deliberately. The
 * caller is gated by EnsureSuperAdmin middleware + StructurePolicy at the
 * route layer, so reaching this service implies the user is a platform
 * operator with no tenant binding.
 *
 * Distinct from CrossTenantQueryService which is reserved for the future
 * Module 9 anonymised benchmark (returns bucketed/anonymised aggregates
 * only). This service returns identifying tenant data because the platform
 * operator legitimately needs to see "Structure XYZ has 12 critical
 * incidents open".
 */
final class PlatformStatsService
{
    /**
     * Cross-tenant top-line KPIs for the platform dashboard.
     *
     * @return array<string, int>
     */
    public function platformKpis(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        return [
            'total_structures' => Structure::query()->count(),
            'active_structures' => Structure::query()
                ->where('status', StructureStatus::Active->value)
                ->count(),
            'suspended_structures' => Structure::query()
                ->where('status', StructureStatus::Suspended->value)
                ->count(),
            'structures_premium' => Structure::query()
                ->where('tier', StructureTier::Premium->value)
                ->count(),
            'structures_pro' => Structure::query()
                ->where('tier', StructureTier::Pro->value)
                ->count(),
            'structures_essential' => Structure::query()
                ->where('tier', StructureTier::Essential->value)
                ->count(),
            'total_users' => User::query()
                ->whereNull('deleted_at')
                ->where('is_platform_admin', false)
                ->count(),
            'total_intervenants' => User::query()
                ->whereNull('deleted_at')
                ->where('type', UserType::Intervenant->value)
                ->count(),
            'total_beneficiaries' => Beneficiary::query()
                ->withoutGlobalScope(StructureScope::class)
                ->count(),
            'interventions_this_month' => Intervention::query()
                ->withoutGlobalScope(StructureScope::class)
                ->where('planned_date', '>=', $startOfMonth)
                ->count(),
            'interventions_in_progress' => Intervention::query()
                ->withoutGlobalScope(StructureScope::class)
                ->where('status', InterventionStatus::InProgress->value)
                ->count(),
            'incidents_open' => Incident::query()
                ->withoutGlobalScope(StructureScope::class)
                ->where('statut', '!=', StatutIncident::Clos->value)
                ->count(),
            'incidents_critical_open' => Incident::query()
                ->withoutGlobalScope(StructureScope::class)
                ->whereIn('gravite', [
                    GraviteIncident::Grave->value,
                    GraviteIncident::Critique->value,
                ])
                ->where('statut', '!=', StatutIncident::Clos->value)
                ->count(),
            'mrr_estimate_eur' => $this->estimateMrr(),
        ];
    }

    /**
     * Latest provisioned structures (most recent first).
     *
     * @return list<array<string, mixed>>
     */
    public function recentStructures(int $limit = 5): array
    {
        return Structure::query()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Structure $s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'type_label' => $s->type->label(),
                'tier_label' => $s->tier->label(),
                'status' => $s->status->value,
                'status_label' => $s->status->label(),
                'created_at' => $s->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * Per-tenant activity row for the structures table.
     *
     * @return list<array<string, mixed>>
     */
    public function structuresWithActivity(int $limit = 25): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $structures = Structure::query()
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $usersByStructure = User::query()
            ->whereIn('structure_id', $structures->pluck('id'))
            ->where('is_platform_admin', false)
            ->whereNull('deleted_at')
            ->selectRaw('structure_id, count(*) as total')
            ->groupBy('structure_id')
            ->pluck('total', 'structure_id');

        $beneficiariesByStructure = Beneficiary::query()
            ->withoutGlobalScope(StructureScope::class)
            ->whereIn('structure_id', $structures->pluck('id'))
            ->selectRaw('structure_id, count(*) as total')
            ->groupBy('structure_id')
            ->pluck('total', 'structure_id');

        $interventionsThisMonthByStructure = Intervention::query()
            ->withoutGlobalScope(StructureScope::class)
            ->whereIn('structure_id', $structures->pluck('id'))
            ->where('planned_date', '>=', $startOfMonth)
            ->selectRaw('structure_id, count(*) as total')
            ->groupBy('structure_id')
            ->pluck('total', 'structure_id');

        $criticalIncidentsByStructure = Incident::query()
            ->withoutGlobalScope(StructureScope::class)
            ->whereIn('structure_id', $structures->pluck('id'))
            ->whereIn('gravite', [
                GraviteIncident::Grave->value,
                GraviteIncident::Critique->value,
            ])
            ->where('statut', '!=', StatutIncident::Clos->value)
            ->selectRaw('structure_id, count(*) as total')
            ->groupBy('structure_id')
            ->pluck('total', 'structure_id');

        return $structures
            ->map(fn (Structure $s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'type_label' => $s->type->label(),
                'tier' => $s->tier->value,
                'tier_label' => $s->tier->label(),
                'status' => $s->status->value,
                'status_label' => $s->status->label(),
                'users_count' => (int) ($usersByStructure[$s->id] ?? 0),
                'beneficiaries_count' => (int) ($beneficiariesByStructure[$s->id] ?? 0),
                'interventions_this_month' => (int) ($interventionsThisMonthByStructure[$s->id] ?? 0),
                'critical_incidents_open' => (int) ($criticalIncidentsByStructure[$s->id] ?? 0),
            ])
            ->all();
    }

    /**
     * Critical/grave incidents currently open across all tenants. Surface
     * for the platform operator to spot structures in trouble at a glance.
     *
     * @return list<array<string, mixed>>
     */
    public function recentCriticalIncidents(int $limit = 8): array
    {
        return Incident::query()
            ->withoutGlobalScope(StructureScope::class)
            ->with(['structure'])
            ->whereIn('gravite', [
                GraviteIncident::Grave->value,
                GraviteIncident::Critique->value,
            ])
            ->where('statut', '!=', StatutIncident::Clos->value)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get()
            ->map(fn (Incident $i) => [
                'id' => $i->id,
                'structure_name' => $i->structure?->name ?? '—',
                'structure_code' => $i->structure?->code ?? '—',
                'categorie' => $i->categorie->value,
                'gravite' => $i->gravite->value,
                'statut' => $i->statut->value,
                'occurred_at' => $i->occurred_at?->toIso8601String(),
                'occurred_at_human' => $i->occurred_at?->diffForHumans(),
            ])
            ->all();
    }

    /**
     * Naïve MRR estimate: sum of (paying users × tier price). Excludes
     * platform admins and suspended structures. Real billing model lives
     * in Phase 2 — this is purely a "feel good" KPI for the dashboard.
     */
    private function estimateMrr(): int
    {
        $structuresWithCounts = Structure::query()
            ->where('status', StructureStatus::Active->value)
            ->get(['id', 'tier']);

        if ($structuresWithCounts->isEmpty()) {
            return 0;
        }

        $usersByStructure = User::query()
            ->whereIn('structure_id', $structuresWithCounts->pluck('id'))
            ->where('is_platform_admin', false)
            ->whereNull('deleted_at')
            ->selectRaw('structure_id, count(*) as total')
            ->groupBy('structure_id')
            ->pluck('total', 'structure_id');

        return $structuresWithCounts->reduce(function (int $acc, Structure $s) use ($usersByStructure): int {
            $users = (int) ($usersByStructure[$s->id] ?? 0);

            return $acc + ($users * $s->tier->monthlyPricePerUser());
        }, 0);
    }
}
