<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\QvctCampaign;
use App\Models\QvctResponse;
use App\Models\QvctWeakSignal;
use Illuminate\Support\Collection;

/**
 * Read-only aggregation service for the per-team psychosocial risk
 * cartography. Spec: PHASE2_PROGRESS.md M3.15 + CDC §M3 line
 * "Psychosocial risk cartography per team / secteur".
 *
 * Produces a per-team snapshot:
 *   {
 *     team_tag => null|"team_paris"|...
 *     response_count: int
 *     mean_scores: { baisse_morale: 3.2, surcharge: 2.1, ... }
 *     active_signals: int          // un-acknowledged weak signals
 *     last_signal_severity: int    // 1-3, max
 *   }
 *
 * Anonymity: all outputs are aggregates over ≥ MIN_TEAM_SIZE responses;
 * teams below the threshold are dropped from the result rather than
 * surfaced as "team X has 1 response, mean Y" (which would re-identify
 * the only respondent). MIN_TEAM_SIZE matches WeakSignalDetector for
 * consistent semantics.
 */
class PsychosocialRiskCartographyService
{
    public const MIN_TEAM_SIZE = WeakSignalDetector::MIN_SAMPLE;

    /**
     * @return Collection<int, array{team_tag: ?string, response_count: int, mean_scores: array<string, float>, active_signals: int, last_signal_severity: int}>
     */
    public function cartographyFor(QvctCampaign $campaign): Collection
    {
        $questionnaire = $campaign->questionnaire;
        $categoriesByQuestionKey = collect($questionnaire->questions)
            ->keyBy('key')
            ->map(fn (array $q): ?string => $q['category'] ?? null)
            ->filter()
            ->all();

        $signals = QvctWeakSignal::query()
            ->where('campaign_id', $campaign->id)
            ->whereNull('acknowledged_at')
            ->get()
            ->groupBy('team_tag');

        return QvctResponse::query()
            ->where('campaign_id', $campaign->id)
            ->get()
            ->groupBy('team_tag')
            ->reject(fn ($group) => $group->count() < self::MIN_TEAM_SIZE)
            ->map(function ($group, $teamTag) use ($categoriesByQuestionKey, $signals): array {
                $teamTagOrNull = $teamTag === '' ? null : $teamTag;

                $perCategoryScores = [];
                foreach ($group as $response) {
                    /** @var QvctResponse $response */
                    foreach ($response->answers as $key => $value) {
                        $category = $categoriesByQuestionKey[$key] ?? null;
                        if ($category === null) {
                            continue;
                        }
                        $perCategoryScores[$category][] = (float) $value;
                    }
                }

                $meanScores = [];
                foreach ($perCategoryScores as $category => $scores) {
                    $meanScores[$category] = round(array_sum($scores) / count($scores), 2);
                }

                $teamSignals = $signals->get($teamTagOrNull) ?? collect();

                return [
                    'team_tag' => $teamTagOrNull,
                    'response_count' => $group->count(),
                    'mean_scores' => $meanScores,
                    'active_signals' => $teamSignals->count(),
                    'last_signal_severity' => (int) $teamSignals->max('severity') ?: 0,
                ];
            })
            ->values();
    }
}
