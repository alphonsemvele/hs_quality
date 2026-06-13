<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditRun;

/**
 * Pure scoring service. Spec: PHASE2_PROGRESS.md M6.12.
 *
 * Computes per-run aggregates from already-recorded responses:
 *   - total_score: sum of response.score across all responses
 *   - max_score:   sum of grid_item.max_points across all items in
 *                  the grid (NOT just answered items — unanswered
 *                  items still count toward max so partial completion
 *                  shows up as a low percentage)
 *   - percentage:  total_score / max_score × 100, two-decimal
 *
 * The service does no DB writes; AuditExecutionService::finalise
 * persists the result. This keeps scoring testable without a DB
 * round-trip and keeps the lifecycle/state-machine logic in one
 * dedicated service.
 */
class AuditScoringService
{
    /**
     * @return array{
     *   total_score: float,
     *   max_score: float,
     *   percentage: float,
     *   excluded_count: int,
     *   evaluated_count: int,
     *   conformity_rate: float,
     *   gap_rate: float,
     * }
     */
    public function score(AuditRun $run): array
    {
        // Items the user explicitly marked "Non applicable" don't count
        // toward max_score (would otherwise penalize an audit that legally
        // can't apply a criterion). The HAS cotation NA is the canonical
        // case; the existing scales never produce a NA so they're untouched.
        $excludedItemIds = $run->responses()
            ->where('cotation', 'NA')
            ->pluck('audit_grid_item_id')
            ->all();

        $itemsQuery = $run->grid->items();
        if ($excludedItemIds !== []) {
            $itemsQuery->whereNotIn('id', $excludedItemIds);
        }
        $maxScore = (float) $itemsQuery->sum('max_points');

        if ($maxScore === 0.0) {
            return [
                'total_score' => 0.0,
                'max_score' => 0.0,
                'percentage' => 0.0,
                'excluded_count' => count($excludedItemIds),
                'evaluated_count' => 0,
                'conformity_rate' => 0.0,
                'gap_rate' => 0.0,
            ];
        }

        $totalScore = (float) $run->responses()->sum('score');
        $percentage = round($totalScore / $maxScore * 100, 2);

        // HAS-cotation aware ratios. For other scales, the cotation column
        // stays NULL so all three counts collapse to 0 — rates are 0.
        $cotationCounts = $run->responses()
            ->selectRaw('cotation, COUNT(*) as c')
            ->whereNotNull('cotation')
            ->groupBy('cotation')
            ->pluck('c', 'cotation');

        $countA = (int) ($cotationCounts['A'] ?? 0);
        $countB = (int) ($cotationCounts['B'] ?? 0);
        $countC = (int) ($cotationCounts['C'] ?? 0);
        $countD = (int) ($cotationCounts['D'] ?? 0);
        $evaluatedExcludingNa = $countA + $countB + $countC + $countD;

        $conformityRate = $evaluatedExcludingNa > 0
            ? round(($countA + $countB) / $evaluatedExcludingNa * 100, 2)
            : 0.0;
        $gapRate = $evaluatedExcludingNa > 0
            ? round(($countC + $countD) / $evaluatedExcludingNa * 100, 2)
            : 0.0;

        return [
            'total_score' => round($totalScore, 2),
            'max_score' => round($maxScore, 2),
            'percentage' => $percentage,
            'excluded_count' => count($excludedItemIds),
            'evaluated_count' => $evaluatedExcludingNa,
            'conformity_rate' => $conformityRate,
            'gap_rate' => $gapRate,
        ];
    }
}
