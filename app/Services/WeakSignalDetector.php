<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QvctWeakSignalType;
use App\Models\QvctCampaign;
use App\Models\QvctResponse;
use App\Models\QvctWeakSignal;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pure scoring service that converts a campaign's responses into weak
 * signals per (team_tag × signal_type). Spec: PHASE2_PROGRESS.md M3.8 +
 * CDC §M3 line "Weak-signal detection: morale drop, overload, relational
 * conflicts".
 *
 * Algorithm (intentionally simple — explainable to a référent RH):
 *   1. Group answers by team_tag (null tag = structure-wide bucket).
 *   2. For each question whose `category` matches a known signal type,
 *      compute the mean of all answers (assumed 1-5 Likert scale).
 *   3. If mean ≤ THRESHOLD and sample_size ≥ MIN_SAMPLE, emit a signal.
 *      Severity scales with how far below threshold the mean falls.
 *
 * The detector is idempotent at the database level: it deletes existing
 * un-acknowledged signals for the campaign before re-emitting. Already-
 * acknowledged signals are preserved (operator decision is sticky).
 */
class WeakSignalDetector
{
    /**
     * Likert mean at or below which a signal fires. 1-5 scale where
     * 1 = strongly negative, 5 = strongly positive — anything ≤ 2.5
     * means the average respondent is unhappy with the dimension.
     */
    public const THRESHOLD = 2.5;

    /**
     * Minimum responses to consider a team's signal statistically
     * meaningful. Below this, low scores are dismissed as noise (one
     * grumpy intervenant in a 4-person team would otherwise trigger
     * every category).
     */
    public const MIN_SAMPLE = 3;

    /**
     * @return Collection<int, QvctWeakSignal>
     */
    public function detectFor(QvctCampaign $campaign): Collection
    {
        // Reset un-acknowledged signals — operator-acknowledged ones
        // remain (sticky decision).
        QvctWeakSignal::query()
            ->where('campaign_id', $campaign->id)
            ->whereNull('acknowledged_at')
            ->delete();

        $questionnaire = $campaign->questionnaire;
        $categoriesByQuestionKey = collect($questionnaire->questions)
            ->keyBy('key')
            ->map(fn (array $q): ?string => $q['category'] ?? null)
            ->filter()
            ->all();

        if ($categoriesByQuestionKey === []) {
            return new Collection;
        }

        $responses = $campaign->responses()->get();
        $byTeam = $responses->groupBy('team_tag');

        $emitted = new Collection;

        foreach ($byTeam as $teamTag => $teamResponses) {
            // Cast nullable team tag to a real null for the column.
            $teamTagOrNull = $teamTag === '' ? null : $teamTag;

            $perCategoryScores = [];

            foreach ($teamResponses as $response) {
                /** @var QvctResponse $response */
                foreach ($response->answers as $key => $value) {
                    $category = $categoriesByQuestionKey[$key] ?? null;
                    if ($category === null) {
                        continue;
                    }
                    $perCategoryScores[$category][] = (int) $value;
                }
            }

            foreach ($perCategoryScores as $category => $scores) {
                $sample = count($scores);
                if ($sample < self::MIN_SAMPLE) {
                    continue;
                }

                $mean = array_sum($scores) / $sample;
                if ($mean > self::THRESHOLD) {
                    continue;
                }

                $signalType = QvctWeakSignalType::tryFrom($category);
                if ($signalType === null) {
                    continue;
                }

                // Severity: how far below threshold (1-3 buckets).
                $severity = match (true) {
                    $mean <= 1.5 => 3,
                    $mean <= 2.0 => 2,
                    default => 1,
                };

                $signal = QvctWeakSignal::create([
                    'structure_id' => $campaign->structure_id,
                    'campaign_id' => $campaign->id,
                    'team_tag' => $teamTagOrNull,
                    'signal_type' => $signalType->value,
                    'details' => [
                        'mean_score' => round($mean, 2),
                        'threshold' => self::THRESHOLD,
                        'sample_size' => $sample,
                        'summary' => sprintf(
                            '%s détecté(e) — moyenne %.2f sur seuil %.2f (%d réponses).',
                            $signalType->label(),
                            $mean,
                            self::THRESHOLD,
                            $sample,
                        ),
                    ],
                    'severity' => $severity,
                ]);

                $emitted->push($signal);
            }
        }

        return $emitted;
    }
}
