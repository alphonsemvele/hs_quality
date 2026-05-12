<?php

namespace App\Services;

use App\Enums\CarePlanStatus;
use App\Models\CarePlan;
use App\Models\PlannedTask;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Lifecycle operations for PlannedTask. Service-layer enforcement of two
 * invariants that the DB cannot express directly:
 *
 *   1. task.structure_id == task.carePlan.structure_id (denormalised; the
 *      model's booted() listener handles create — this service handles
 *      reparenting if a future feature ever moves a task between plans).
 *
 *   2. Tasks attached to an Archived plan are immutable. The plan is the
 *      audit trail; mutating its tasks after archival would invalidate it.
 *
 * Order management: when a task is created without an explicit task_order,
 * we append it (= max existing order + 1). The UI can later POST a reorder
 * payload — out of scope for this milestone.
 */
class PlannedTaskService
{
    public function create(CarePlan $plan, array $data): PlannedTask
    {
        $this->guardArchived($plan);

        return DB::transaction(function () use ($plan, $data): PlannedTask {
            $data['care_plan_id'] = $plan->id;

            // Auto-append if no order provided.
            $data['task_order'] ??= ($plan->tasks()->max('task_order') ?? -1) + 1;

            return PlannedTask::create($data)->fresh();
        });
    }

    public function update(PlannedTask $task, array $data): PlannedTask
    {
        $this->guardArchived($task->carePlan);

        return DB::transaction(function () use ($task, $data): PlannedTask {
            // Reparenting (changing care_plan_id) is intentionally not
            // supported — too easy to silently violate the structure_id
            // invariant. If a future feature needs it, add a dedicated
            // moveTo() method that re-derives structure_id from the new parent.
            unset($data['care_plan_id'], $data['structure_id']);

            $task->update($data);

            return $task->fresh();
        });
    }

    public function delete(PlannedTask $task): void
    {
        $this->guardArchived($task->carePlan);

        $task->delete();
    }

    /**
     * Atomically re-sequence tasks of a plan to match the given ordered list.
     *
     * @param  array<int, int>  $orderedIds  Task IDs in their desired order.
     */
    public function reorder(CarePlan $plan, array $orderedIds): void
    {
        $this->guardArchived($plan);

        // Validate that all IDs belong to this plan — never trust client input
        // to span plans, that would let the UI swap tasks between plans of
        // possibly different bénéficiaires.
        $owned = $plan->tasks()->whereIn('id', $orderedIds)->pluck('id')->all();
        if (count($owned) !== count($orderedIds)) {
            throw new HttpException(422, 'Some task ids do not belong to this care plan.');
        }

        DB::transaction(function () use ($plan, $orderedIds): void {
            foreach ($orderedIds as $index => $id) {
                $plan->tasks()->where('id', $id)->update(['task_order' => $index]);
            }
        });
    }

    private function guardArchived(CarePlan $plan): void
    {
        if ($plan->status === CarePlanStatus::Archived) {
            throw new HttpException(409, 'Cannot mutate tasks of an archived care plan.');
        }
    }
}
