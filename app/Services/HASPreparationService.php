<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditGridSource;
use App\Enums\HASConformityStatus;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\PacAction;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Phase 2 / M6.19 — HAS preparation guide gap analysis.
 *
 * Compares a finalised HAS audit run's recorded scores against the
 * item max_points to produce a prioritised gap list. The conformity
 * thresholds (70 % / 40 %) follow standard HAS évaluation interne
 * grading for SAAD/SSIAD/SPASAD.
 *
 * Items with no recorded response are treated as non-conforme (0 score)
 * because a criterion skipped during an evaluation counts against the
 * structure in a real HAS review.
 *
 * PAC actions linked to each gap are surfaced so the preparation guide
 * can show "déjà en cours" items without a second round-trip.
 */
class HASPreparationService
{
    public const CONFORMITY_THRESHOLD_PCT = 70.0;

    public const AMELIORER_THRESHOLD_PCT = 40.0;

    /**
     * Analyse a finalised HAS run and return a prioritised gap report.
     *
     * @return array{
     *     run_id: string,
     *     run_title: string,
     *     grid_source: string,
     *     overall_readiness_pct: float,
     *     conformity_threshold_pct: float,
     *     items: list<array{
     *         item_id: string,
     *         title: string,
     *         position: int,
     *         score: float,
     *         max_points: float,
     *         conformity_pct: float,
     *         status: string,
     *         gap: float,
     *         response_id: string|null,
     *         pac_action_ids: list<string>,
     *     }>,
     *     summary: array{conforme: int, a_ameliorer: int, non_conforme: int, total: int},
     * }
     *
     * @throws HttpException 409 if the run is not finalised
     * @throws HttpException 422 if the run's grid is not a HAS grid
     */
    public function analyse(AuditRun $run): array
    {
        if (! $run->isFinalised()) {
            throw new HttpException(409, 'Le guide de préparation HAS est disponible uniquement pour un audit finalisé.');
        }

        $run->loadMissing(['grid.items']);

        if ($run->grid->source !== AuditGridSource::Has) {
            throw new HttpException(422, 'Le guide de préparation HAS n\'est applicable qu\'aux grilles de source HAS.');
        }

        $responsesByItemId = AuditRunResponse::query()
            ->where('audit_run_id', $run->id)
            ->get()
            ->keyBy('audit_grid_item_id');

        $responseIds = $responsesByItemId->pluck('id');

        $pacActionsByResponseId = PacAction::withoutGlobalScopes()
            ->whereIn('source_audit_response_id', $responseIds)
            ->get()
            ->groupBy('source_audit_response_id');

        $items = $run->grid->items
            ->map(function (AuditGridItem $item) use ($responsesByItemId, $pacActionsByResponseId): array {
                $response = $responsesByItemId->get($item->id);
                $maxPoints = (float) $item->max_points;
                $score = $response !== null && $response->score !== null
                    ? (float) $response->score
                    : 0.0;

                $conformityPct = $maxPoints > 0
                    ? round($score / $maxPoints * 100, 2)
                    : 0.0;

                $status = HASConformityStatus::fromPercentage($conformityPct);
                $pacIds = $response !== null
                    ? $pacActionsByResponseId->get($response->id, collect())->pluck('id')->values()->all()
                    : [];

                return [
                    'item_id' => $item->id,
                    'title' => $item->title,
                    'position' => $item->position,
                    'score' => $score,
                    'max_points' => $maxPoints,
                    'conformity_pct' => $conformityPct,
                    'status' => $status->value,
                    'gap' => round($maxPoints - $score, 2),
                    'response_id' => $response?->id,
                    'pac_action_ids' => $pacIds,
                ];
            })
            ->sortBy([
                fn (array $a, array $b) => HASConformityStatus::from($a['status'])->priority()
                    <=> HASConformityStatus::from($b['status'])->priority(),
                fn (array $a, array $b) => $b['gap'] <=> $a['gap'],
            ])
            ->values()
            ->all();

        $summary = array_count_values(array_column($items, 'status'));

        $overallPct = (float) $run->max_score > 0
            ? round((float) $run->score / (float) $run->max_score * 100, 2)
            : 0.0;

        return [
            'run_id' => $run->id,
            'run_title' => $run->title,
            'grid_source' => $run->grid->source->value,
            'overall_readiness_pct' => $overallPct,
            'conformity_threshold_pct' => self::CONFORMITY_THRESHOLD_PCT,
            'items' => $items,
            'summary' => [
                'conforme' => $summary[HASConformityStatus::Conforme->value] ?? 0,
                'a_ameliorer' => $summary[HASConformityStatus::AAmeliorer->value] ?? 0,
                'non_conforme' => $summary[HASConformityStatus::NonConforme->value] ?? 0,
                'total' => count($items),
            ],
        ];
    }
}
