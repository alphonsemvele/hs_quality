<?php

namespace App\Services;

use App\Enums\InterventionStatus;
use App\Enums\VisitMode;
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

            return $intervention->fresh();
        });
    }

    /**
     * Check out: in_progress → completed.
     * Records actual_end_at and optional report text.
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
                'report_text' => $data['report_text'] ?? $intervention->report_text,
            ]);

            return $intervention->fresh();
        });
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

            return $intervention->fresh();
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

            return $intervention->fresh();
        });
    }

    public function delete(Intervention $intervention): void
    {
        $intervention->delete();
    }
}
