<?php

namespace App\Services;

use App\Enums\InterventionStatus;
use App\Models\Incident;
use App\Models\Intervention;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardStatsService
{
    private const TTL = 300; // 5 minutes

    public function stats(int|string $structureId): array
    {
        return Cache::tags(["structure:{$structureId}:dashboard"])
            ->remember("dashboard:stats:{$structureId}", self::TTL, function (): array {
                return [
                    ...$this->interventionStats(),
                    ...$this->incidentStats(),
                ];
            });
    }

    private function interventionStats(): array
    {
        $today = Carbon::today();
        $monthStart = Carbon::today()->startOfMonth();

        $base = Intervention::query()->whereDate('planned_date', $today);

        return [
            'interventions_today' => (clone $base)->count(),
            'interventions_ce_mois' => Intervention::query()
                ->whereDate('planned_date', '>=', $monthStart)
                ->count(),
            'in_progress_count' => Intervention::query()
                ->where('status', InterventionStatus::InProgress->value)
                ->count(),
            'completed_today' => (clone $base)
                ->where('status', InterventionStatus::Completed->value)
                ->count(),
            'cancelled_today' => (clone $base)
                ->where('status', InterventionStatus::Cancelled->value)
                ->count(),
            'missed_today' => (clone $base)
                ->where('status', InterventionStatus::Missed->value)
                ->count(),
        ];
    }

    private function incidentStats(): array
    {
        return [
            'incidents_declares' => Incident::query()
                ->where('statut', 'declare')
                ->count(),
            'incidents_en_cours' => Incident::query()
                ->whereIn('statut', ['en_analyse', 'plan_actions'])
                ->count(),
            'incidents_graves' => Incident::query()
                ->whereIn('gravite', ['grave', 'critique'])
                ->whereNotIn('statut', ['clos'])
                ->count(),
            'incidents_ce_mois' => Incident::query()
                ->whereDate('occurred_at', '>=', Carbon::today()->startOfMonth())
                ->count(),
        ];
    }

    /**
     * Returns the 5 most recent open incidents for the dashboard feed.
     * Not cached — feed is always fresh.
     */
    public function recentIncidents(int $limit = 5): Collection
    {
        return Incident::query()
            ->with(['declarant:id,first_name,last_name'])
            ->whereNotIn('statut', ['clos'])
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }
}
