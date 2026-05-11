<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditRunStatus;
use App\Models\AuditGrid;
use App\Models\AuditGridItem;
use App\Models\AuditRun;
use App\Models\AuditRunResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Lifecycle of an audit run: draft → in_progress → finalised.
 * Spec: PHASE2_PROGRESS.md M6.11.
 *
 * State invariants enforced here:
 *   - cannot recordResponse on a finalised run
 *   - cannot finalise twice
 *   - finalise locks score + max_score (frozen at finalise time so a
 *     later edit of the underlying grid never silently mutates a
 *     historical score)
 */
class AuditExecutionService
{
    public function __construct(private readonly AuditScoringService $scoring) {}

    public function start(AuditGrid $grid, string $title, string $runDate): AuditRun
    {
        return AuditRun::create([
            'structure_id' => $grid->structure_id,
            'audit_grid_id' => $grid->id,
            'title' => $title,
            'run_date' => $runDate,
            'status' => AuditRunStatus::Draft->value,
        ]);
    }

    public function recordResponse(
        AuditRun $run,
        AuditGridItem $item,
        ?float $score,
        ?string $comment,
        ?string $evidenceUrl,
        ?User $recordedBy,
    ): AuditRunResponse {
        if ($run->isFinalised()) {
            throw new HttpException(409, 'Cannot record responses on a finalised audit run.');
        }

        if ($item->audit_grid_id !== $run->audit_grid_id) {
            throw new HttpException(422, 'Item does not belong to this run\'s grid.');
        }

        return DB::transaction(function () use ($run, $item, $score, $comment, $evidenceUrl, $recordedBy): AuditRunResponse {
            // Move out of Draft once any response lands.
            if ($run->status === AuditRunStatus::Draft) {
                $run->update(['status' => AuditRunStatus::InProgress->value]);
            }

            return AuditRunResponse::updateOrCreate(
                [
                    'audit_run_id' => $run->id,
                    'audit_grid_item_id' => $item->id,
                ],
                [
                    'structure_id' => $run->structure_id,
                    'score' => $score,
                    'comment' => $comment,
                    'evidence_url' => $evidenceUrl,
                    'recorded_by' => $recordedBy?->id,
                    'recorded_at' => now(),
                ],
            );
        });
    }

    public function finalise(AuditRun $run, User $finaliser): AuditRun
    {
        if ($run->isFinalised()) {
            throw new HttpException(409, 'Audit run is already finalised.');
        }

        return DB::transaction(function () use ($run, $finaliser): AuditRun {
            $aggregates = $this->scoring->score($run);

            $run->update([
                'status' => AuditRunStatus::Finalised->value,
                'score' => $aggregates['total_score'],
                'max_score' => $aggregates['max_score'],
                'finalised_by' => $finaliser->id,
                'finalised_at' => now(),
            ]);

            return $run->fresh();
        });
    }
}
