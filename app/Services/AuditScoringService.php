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
     * @return array{total_score: float, max_score: float, percentage: float}
     */
    public function score(AuditRun $run): array
    {
        $maxScore = (float) $run->grid->items()->sum('max_points');

        if ($maxScore === 0.0) {
            return ['total_score' => 0.0, 'max_score' => 0.0, 'percentage' => 0.0];
        }

        $totalScore = (float) $run->responses()->sum('score');
        $percentage = round($totalScore / $maxScore * 100, 2);

        return [
            'total_score' => round($totalScore, 2),
            'max_score' => round($maxScore, 2),
            'percentage' => $percentage,
        ];
    }
}
