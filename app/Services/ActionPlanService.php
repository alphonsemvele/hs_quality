<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QvctActionPlanItemStatus;
use App\Enums\QvctActionPlanStatus;
use App\Models\QvctActionPlan;
use App\Models\QvctActionPlanItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * QVCT action-plan lifecycle: draft → published → closed.
 * Spec: PHASE2_PROGRESS.md M3.29 + CDC §M3 line "QVCT action plan with
 * impact measurement".
 *
 * Lifecycle invariants enforced here:
 *   - cannot publish a closed plan
 *   - cannot close a draft plan (publish first)
 *   - items can only be added/removed while status is draft (after
 *     publish, items can update status + record impact, but the plan's
 *     scope is locked)
 */
class ActionPlanService
{
    public function draft(User $author, array $data): QvctActionPlan
    {
        return QvctActionPlan::create([
            'structure_id' => $author->structure_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'target_quarter' => $data['target_quarter'] ?? null,
            'status' => QvctActionPlanStatus::Draft->value,
            'created_by' => $author->id,
        ]);
    }

    public function publish(QvctActionPlan $plan, User $publisher): QvctActionPlan
    {
        if ($plan->isClosed()) {
            throw new HttpException(409, 'Cannot publish a closed action plan.');
        }

        if ($plan->isPublished()) {
            return $plan;
        }

        $plan->update([
            'status' => QvctActionPlanStatus::Published->value,
            'published_by' => $publisher->id,
            'published_at' => now(),
        ]);

        return $plan->fresh();
    }

    public function close(QvctActionPlan $plan, User $closer): QvctActionPlan
    {
        if ($plan->isDraft()) {
            throw new HttpException(409, 'Publish the action plan before closing it.');
        }

        if ($plan->isClosed()) {
            return $plan;
        }

        $plan->update([
            'status' => QvctActionPlanStatus::Closed->value,
            'closed_by' => $closer->id,
            'closed_at' => now(),
        ]);

        return $plan->fresh();
    }

    public function addItem(QvctActionPlan $plan, array $data): QvctActionPlanItem
    {
        if (! $plan->isDraft()) {
            throw new HttpException(409, 'Items can only be added while the plan is a draft.');
        }

        $item = QvctActionPlanItem::create([
            'structure_id' => $plan->structure_id,
            'action_plan_id' => $plan->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'responsible_user_id' => $data['responsible_user_id'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status' => QvctActionPlanItemStatus::Pending->value,
            'impact_measurement_target' => $data['impact_measurement_target'] ?? null,
        ]);

        return $item->fresh();
    }

    public function removeItem(QvctActionPlanItem $item): void
    {
        if (! $item->actionPlan->isDraft()) {
            throw new HttpException(409, 'Items can only be removed while the plan is a draft.');
        }

        $item->delete();
    }

    public function updateItemStatus(QvctActionPlanItem $item, string $status): QvctActionPlanItem
    {
        if ($item->actionPlan->isClosed()) {
            throw new HttpException(409, 'Cannot update items of a closed action plan.');
        }

        $item->update(['status' => $status]);

        return $item->fresh();
    }

    /**
     * Record the actual impact achieved against the pre-set target.
     * Allowed on published OR closed plans (impact may come in after a
     * plan closes — keep the door open for late-arriving figures).
     */
    public function recordImpact(QvctActionPlanItem $item, string $actual): QvctActionPlanItem
    {
        return DB::transaction(function () use ($item, $actual): QvctActionPlanItem {
            $item->update([
                'impact_measurement_actual' => $actual,
                'impact_measured_at' => now(),
            ]);

            return $item->fresh();
        });
    }
}
