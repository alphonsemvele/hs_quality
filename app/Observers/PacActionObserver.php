<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PacAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PacAction observer — dashboard cache invalidation + structured
 * status-transition logging. Companion to PacObserver; same rationale:
 * Auditable handles the formal compliance trail, Log::info gives
 * ops a queryable thin layer for monitoring follow-up overdue
 * actions, who reassigned what, etc.
 */
class PacActionObserver
{
    public function saved(PacAction $action): void
    {
        $this->flush($action->structure_id);
        $this->logStatusTransition($action);
    }

    public function deleted(PacAction $action): void
    {
        $this->flush($action->structure_id);

        Log::info('PAC action deleted', [
            'action_id' => $action->id,
            'pac_id' => $action->pac_id,
            'structure_id' => $action->structure_id,
            'status_at_delete' => $action->status?->value,
        ]);
    }

    private function logStatusTransition(PacAction $action): void
    {
        if (! $action->wasChanged('status')) {
            return;
        }

        $originalStatus = $action->getOriginal('status');

        Log::info('PAC action status transition', [
            'action_id' => $action->id,
            'pac_id' => $action->pac_id,
            'structure_id' => $action->structure_id,
            'from' => $originalStatus instanceof \BackedEnum ? $originalStatus->value : $originalStatus,
            'to' => $action->status?->value,
            'responsible_user_id' => $action->responsible_user_id,
            'due_date' => $action->due_date?->toDateString(),
        ]);
    }

    private function flush(int|string $structureId): void
    {
        Cache::tags(["structure:{$structureId}:dashboard"])->flush();
    }
}
