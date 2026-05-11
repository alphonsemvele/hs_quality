<?php

namespace App\Services;

use App\Enums\InterventionStatus;
use App\Enums\VisitMode;
use App\Events\InterventionStatusChanged;
use App\Models\Beneficiary;
use App\Models\Intervention;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InterventionService
{
    /**
     * Plan a new intervention (coordinateur-initiated, status = planned).
     */
    public function create(array $data, User $createdBy): Intervention
    {
        return DB::transaction(function () use ($data, $createdBy): Intervention {
            $data['structure_id'] = $createdBy->structure_id;
            $data['visit_mode'] ??= VisitMode::Web->value;
            $data['status'] = InterventionStatus::Planned->value;

            return Intervention::create($data)->fresh();
        });
    }

    /**
     * Update editable fields on a non-terminal intervention.
     */
    public function update(Intervention $intervention, array $data): Intervention
    {
        if ($intervention->isTerminal()) {
            throw new HttpException(409, 'Cannot update a completed, cancelled, or missed intervention.');
        }

        return DB::transaction(function () use ($intervention, $data): Intervention {
            // Prevent reparenting — structure and beneficiary are immutable after creation.
            unset($data['structure_id'], $data['beneficiary_id'], $data['status']);

            $intervention->update($data);

            return $intervention->fresh();
        });
    }

    /**
     * Check in: planned → in_progress.
     * Records actual_start_at and optional GPS coordinates.
     */
    public function checkIn(Intervention $intervention, array $data = []): Intervention
    {
        if (! $intervention->isPlanned()) {
            throw new HttpException(409, 'Intervention is not in planned status.');
        }

        return DB::transaction(function () use ($intervention, $data): Intervention {
            $intervention->update([
                'status' => InterventionStatus::InProgress->value,
                'actual_start_at' => now(),
                'checkin_latitude' => $data['latitude'] ?? null,
                'checkin_longitude' => $data['longitude'] ?? null,
            ]);

            $fresh = $intervention->fresh();
            InterventionStatusChanged::dispatch($fresh, InterventionStatus::InProgress);

            return $fresh;
        });
    }

    /**
     * Check out: in_progress → completed.
     * Records actual_end_at and optional report text.
     *
     * Concurrent-write semantics: if `report_text` is supplied AND the
     * intervention already carries a different `report_text` (a previous
     * sync/HTTP write got there first), the two values are merged with
     * conflict markers via {@see self::mergeReportText()} so neither
     * intervenant's narrative is lost. Empty / identical incoming text is
     * treated as a no-op.
     */
    public function checkOut(Intervention $intervention, array $data = []): Intervention
    {
        if (! $intervention->isInProgress()) {
            throw new HttpException(409, 'Intervention is not in progress.');
        }

        return DB::transaction(function () use ($intervention, $data): Intervention {
            $intervention->update([
                'status' => InterventionStatus::Completed->value,
                'actual_end_at' => now(),
                'report_text' => $this->mergeReportText(
                    $intervention->report_text,
                    $data['report_text'] ?? null,
                ),
            ]);

            $fresh = $intervention->fresh();
            InterventionStatusChanged::dispatch($fresh, InterventionStatus::Completed);

            return $fresh;
        });
    }

    /**
     * Submit / amend the free-text report on an intervention.
     *
     * Permitted while the intervention is in_progress (intervenant fills
     * the report mid-visit) or completed (post-checkout amendment — common
     * when an intervenant remembers a detail after closing). Terminal
     * statuses other than completed (cancelled, missed) are rejected — no
     * report is recorded for visits that didn't happen.
     *
     * Concurrent submissions merge via {@see self::mergeReportText()} so
     * two intervenants flushing offline queues that both touched this
     * report keep both narratives instead of LWW-clobbering one.
     */
    public function submitReport(Intervention $intervention, string $reportText, ?User $author = null): Intervention
    {
        if (! $intervention->isInProgress() && ! $intervention->isCompleted()) {
            throw new HttpException(409, 'Report can only be submitted on in-progress or completed interventions.');
        }

        return DB::transaction(function () use ($intervention, $reportText): Intervention {
            $intervention->update([
                'report_text' => $this->mergeReportText(
                    $intervention->report_text,
                    $reportText,
                ),
            ]);

            return $intervention->fresh();
        });
    }

    /**
     * Merge two free-text report values with git-style conflict markers
     * when both are non-empty and differ. If either side is blank or
     * trims to the same content, the non-empty / canonical value wins
     * (no marker noise for trivial cases).
     *
     * Format intentionally mirrors `git merge` output so a coordinateur
     * skimming a flagged report immediately recognises it as a merge
     * needing manual reconciliation:
     *
     *   <<<<<<< saved
     *   …existing text…
     *   =======
     *   …incoming text…
     *   >>>>>>> incoming
     *
     * If `existing` already contains a `<<<<<<< saved` line we still wrap
     * it again — accumulating offline retries are rare and visible
     * marker stacking is preferable to silent loss.
     */
    public function mergeReportText(?string $existing, ?string $incoming): ?string
    {
        $existingTrimmed = $existing !== null && trim($existing) !== '' ? trim($existing) : null;
        $incomingTrimmed = $incoming !== null && trim($incoming) !== '' ? trim($incoming) : null;

        if ($incomingTrimmed === null) {
            return $existingTrimmed;
        }

        if ($existingTrimmed === null) {
            return $incomingTrimmed;
        }

        if ($existingTrimmed === $incomingTrimmed) {
            return $existingTrimmed;
        }

        return "<<<<<<< saved\n{$existingTrimmed}\n=======\n{$incomingTrimmed}\n>>>>>>> incoming";
    }

    /**
     * Cancel: planned or in_progress → cancelled.
     */
    public function cancel(Intervention $intervention, string $reason): Intervention
    {
        if ($intervention->isTerminal()) {
            throw new HttpException(409, 'Intervention is already in a terminal state.');
        }

        return DB::transaction(function () use ($intervention, $reason): Intervention {
            $intervention->update([
                'status' => InterventionStatus::Cancelled->value,
                'cancellation_reason' => $reason,
            ]);

            $fresh = $intervention->fresh();
            InterventionStatusChanged::dispatch($fresh, InterventionStatus::Cancelled);

            return $fresh;
        });
    }

    /**
     * Mark missed: planned → missed (for no-shows after planned_date passes).
     */
    public function markMissed(Intervention $intervention): Intervention
    {
        if (! $intervention->isPlanned()) {
            throw new HttpException(409, 'Only planned interventions can be marked as missed.');
        }

        return DB::transaction(function () use ($intervention): Intervention {
            $intervention->update(['status' => InterventionStatus::Missed->value]);

            $fresh = $intervention->fresh();
            InterventionStatusChanged::dispatch($fresh, InterventionStatus::Missed);

            return $fresh;
        });
    }

    /**
     * Sweep all `planned` interventions whose scheduled end time + grace
     * window has passed without check-in and flag them as `missed`.
     *
     * Called from the `interventions:sweep-missed` Artisan command on a
     * cron schedule (see routes/console.php). Returns the count of
     * interventions transitioned, so the command's exit log + the audit
     * trail show what was swept and when.
     *
     * The grace window absorbs late check-ins (intervenant arrived but
     * hasn't tapped "check-in" yet — common in zones blanches).
     */
    public function sweepMissed(int $graceMinutes = 30): int
    {
        $cutoff = now()->subMinutes($graceMinutes);

        // Coarse filter at the DB layer (planned status, end_time present,
        // dated on or before today). Then refine the date+time combination
        // in PHP — `planned_date + planned_end_time` is a Postgres-specific
        // interval cast that doesn't run on SQLite (the test driver).
        // Keeping the comparison in PHP keeps the query DB-portable.
        //
        // Volume: a 200-intervenant tenant rarely has more than ~hundred
        // pending-sweep rows at any moment, so the in-PHP refinement has
        // negligible cost.
        //
        // Bypass tenant scope — this runs from console and must affect
        // every tenant simultaneously.
        $candidates = Intervention::query()
            ->withoutGlobalScopes()
            ->where('status', InterventionStatus::Planned->value)
            ->whereNotNull('planned_end_time')
            ->whereDate('planned_date', '<=', $cutoff->toDateString())
            ->get();

        $count = 0;
        foreach ($candidates as $intervention) {
            $endAt = $intervention->planned_date->copy()->setTimeFromTimeString(
                (string) $intervention->planned_end_time,
            );

            if ($endAt->greaterThan($cutoff)) {
                continue;
            }

            DB::transaction(function () use ($intervention): void {
                $intervention->update(['status' => InterventionStatus::Missed->value]);
                InterventionStatusChanged::dispatch($intervention->fresh(), InterventionStatus::Missed);
            });
            $count++;
        }

        return $count;
    }

    public function delete(Intervention $intervention): void
    {
        $intervention->delete();
    }
}
