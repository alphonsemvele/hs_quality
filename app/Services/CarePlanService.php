<?php

namespace App\Services;

use App\Enums\CarePlanStatus;
use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\PlannedTask;
use Illuminate\Support\Facades\DB;

/**
 * CarePlan lifecycle operations: create, update, activate, archive, and
 * copy-from-template (the "new plan based on another beneficiary's plan"
 * feature per CDC M1). Every mutation is DB-transaction-wrapped.
 *
 * Invariant enforced: a beneficiary has AT MOST ONE plan in the `active`
 * state. Activating a second plan auto-archives the first.
 */
class CarePlanService
{
    public function create(array $data): CarePlan
    {
        return DB::transaction(function () use ($data): CarePlan {
            // ->fresh() so DB-level defaults (status='draft') hydrate onto the
            // returned model rather than being left null in memory.
            return CarePlan::create($data)->fresh();
        });
    }

    public function update(CarePlan $plan, array $data): CarePlan
    {
        return DB::transaction(function () use ($plan, $data): CarePlan {
            $plan->update($data);

            return $plan->fresh();
        });
    }

    /**
     * Promote a Draft plan to Active. If the beneficiary already has an
     * active plan, auto-archives it so the single-active-plan invariant
     * holds.
     */
    public function activate(CarePlan $plan): CarePlan
    {
        return DB::transaction(function () use ($plan): CarePlan {
            // Archive any currently-active plan for the same beneficiary
            CarePlan::query()
                ->where('beneficiary_id', $plan->beneficiary_id)
                ->where('status', CarePlanStatus::Active->value)
                ->whereKeyNot($plan->getKey())
                ->get()
                ->each(fn (CarePlan $other) => $this->archiveInternal($other, 'Remplacé par le plan '.$plan->title));

            $plan->update([
                'status' => CarePlanStatus::Active->value,
                'archived_at' => null,
                'archived_reason' => null,
            ]);

            return $plan->fresh();
        });
    }

    public function archive(CarePlan $plan, string $reason): CarePlan
    {
        return DB::transaction(function () use ($plan, $reason): CarePlan {
            $this->archiveInternal($plan, $reason);

            return $plan->fresh();
        });
    }

    /**
     * Copy a source plan's tasks into a new draft plan for the target
     * beneficiary. Used for "start from another beneficiary's plan as a
     * template". The new plan is always created in Draft status; the
     * operator reviews + activates explicitly.
     */
    public function copyFromTemplate(CarePlan $source, Beneficiary $target, string $title): CarePlan
    {
        if ($source->structure_id !== $target->structure_id) {
            abort(422, 'Cannot copy a care plan across structures.');
        }

        return DB::transaction(function () use ($source, $target, $title): CarePlan {
            $newPlan = CarePlan::create([
                'structure_id' => $target->structure_id,
                'beneficiary_id' => $target->id,
                'title' => $title,
                'objectives' => $source->objectives,
                'start_date' => now()->toDateString(),
                'end_date' => null,
                'status' => CarePlanStatus::Draft->value,
            ]);

            foreach ($source->tasks as $task) {
                PlannedTask::create([
                    'care_plan_id' => $newPlan->id,
                    'structure_id' => $target->structure_id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'frequency' => $task->frequency?->value,
                    'frequency_details' => $task->frequency_details,
                    'duration_minutes' => $task->duration_minutes,
                    'task_order' => $task->task_order,
                    'mandatory' => $task->mandatory,
                ]);
            }

            return $newPlan->fresh(['tasks']);
        });
    }

    private function archiveInternal(CarePlan $plan, string $reason): void
    {
        $plan->update([
            'status' => CarePlanStatus::Archived->value,
            'archived_at' => now(),
            'archived_reason' => $reason,
            'end_date' => $plan->end_date ?? now()->toDateString(),
        ]);
    }
}
