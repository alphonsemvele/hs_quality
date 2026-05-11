<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QvctCampaignStatus;
use App\Models\QvctCampaign;
use App\Models\QvctIndicator;
use App\Models\QvctResponse;
use App\Models\Structure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Periodic snapshotting of QVCT indicators per structure. Spec:
 * PHASE2_PROGRESS.md M3.27 + CDC §M3 line "QVCT indicator tracking".
 *
 * Auto-computed today: barometer_mean_score (mean of all responses to
 * campaigns that closed within the period). Absenteeism, turnover, and
 * work-accident counts stay null on auto-snapshot — those domains aren't
 * modelled yet (no attendance ledger, no worker-incident table). RH
 * fills them in via the API; the snapshot row is the canonical home.
 *
 * Idempotency: re-running snapshotForStructure() for a period that
 * already has a row updates the auto-computed columns in place but
 * leaves manual-entry columns and notes untouched.
 */
class IndicatorIngestionService
{
    /**
     * Take a snapshot for the given structure × period start.
     * `periodStart` is normalised to the first day of its month so a
     * unique-constraint violation cannot happen no matter what date the
     * caller passes.
     */
    public function snapshotForStructure(Structure $structure, ?Carbon $periodStart = null): QvctIndicator
    {
        $start = ($periodStart ?? now())->copy()->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $barometerMean = $this->computeBarometerMean($structure->id, $start, $end);

        return DB::transaction(function () use ($structure, $start, $end, $barometerMean): QvctIndicator {
            // whereDate avoids the SQLite date-vs-datetime literal mismatch:
            // the date cast persists '2026-05-01' as '2026-05-01 00:00:00',
            // so a plain string `where('period_start', '2026-05-01')` never
            // matches and we'd attempt a duplicate insert.
            $existing = QvctIndicator::query()
                ->withoutGlobalScopes()
                ->where('structure_id', $structure->id)
                ->whereDate('period_start', $start->toDateString())
                ->first();

            if ($existing !== null) {
                $existing->update([
                    'period_end' => $end->toDateString(),
                    'barometer_mean_score' => $barometerMean,
                    'captured_at' => now(),
                ]);

                return $existing->fresh();
            }

            return QvctIndicator::create([
                'structure_id' => $structure->id,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'barometer_mean_score' => $barometerMean,
                'captured_at' => now(),
            ]);
        });
    }

    /**
     * Snapshot every structure for the given period. Cron-friendly
     * entry point — bypasses tenant scope (runs from console with no
     * current_structure binding).
     *
     * @return int Count of snapshots taken / updated.
     */
    public function snapshotAllStructures(?Carbon $periodStart = null): int
    {
        $count = 0;
        Structure::query()->each(function (Structure $structure) use (&$count, $periodStart): void {
            $this->snapshotForStructure($structure, $periodStart);
            $count++;
        });

        return $count;
    }

    private function computeBarometerMean(int|string $structureId, Carbon $start, Carbon $end): ?float
    {
        // Gather campaigns whose closed window intersects the period —
        // a campaign that closed on June 5 contributes to the June row.
        $campaignIds = QvctCampaign::query()
            ->withoutGlobalScopes()
            ->where('structure_id', $structureId)
            ->where('status', QvctCampaignStatus::Closed->value)
            ->whereBetween('closes_at', [$start->toDateString(), $end->toDateString()])
            ->pluck('id');

        if ($campaignIds->isEmpty()) {
            return null;
        }

        // Mean of all numeric answers across all responses to those campaigns.
        // The detector uses per-category means; the global indicator is the
        // gross satisfaction average and intentionally simpler — the dashboard
        // tile sits next to per-category cartography for nuance.
        $responses = QvctResponse::query()
            ->withoutGlobalScopes()
            ->whereIn('campaign_id', $campaignIds)
            ->get();

        if ($responses->isEmpty()) {
            return null;
        }

        $total = 0.0;
        $count = 0;
        foreach ($responses as $response) {
            foreach ($response->answers as $value) {
                if (is_numeric($value)) {
                    $total += (float) $value;
                    $count++;
                }
            }
        }

        if ($count === 0) {
            return null;
        }

        return round($total / $count, 2);
    }
}
