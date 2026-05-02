<?php

namespace App\Services;

use App\Enums\InterventionStatus;
use App\Enums\QvctCampaignStatus;
use App\Models\Incident;
use App\Models\Intervention;
use App\Models\QvctCampaign;
use App\Models\QvctResponse;
use App\Models\QvctWeakSignal;
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
                    ...$this->qvctStats(),
                ];
            });
    }

    /**
     * Phase 2 / M3 — dashboard tile (PHASE2_PROGRESS.md M3.16). Operator
     * + référent RH care about: how many campaigns are accepting
     * responses right now, how much engagement we're getting in the
     * current month, and how many weak signals are still untriaged.
     *
     * NOT included: any per-individual figure or any per-team breakdown
     * below MIN_TEAM_SIZE — those would risk re-identifying respondents.
     * The cartography service handles team-level views with its own
     * minimum-sample guard.
     */
    private function qvctStats(): array
    {
        $monthStart = Carbon::today()->startOfMonth();

        return [
            'qvct_campaigns_open' => QvctCampaign::query()
                ->where('status', QvctCampaignStatus::Active->value)
                ->count(),
            'qvct_responses_ce_mois' => QvctResponse::query()
                ->where('submitted_at', '>=', $monthStart)
                ->count(),
            'qvct_weak_signals_outstanding' => QvctWeakSignal::query()
                ->whereNull('acknowledged_at')
                ->count(),
        ];
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
