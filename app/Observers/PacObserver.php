<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Pac;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PAC observer — handles dashboard-cache invalidation on every write
 * AND emits structured Log lines on status transitions for ops
 * triage. The owen-it/laravel-auditing trail (`audits` table) remains
 * the formal compliance record; the log lines are an ergonomic layer
 * for downstream alerting (Datadog, CloudWatch, etc.).
 *
 * Only status changes are logged here — full audit detail (old/new
 * values for any field) is in the audits table, not duplicated in
 * Log to avoid retention-policy collisions.
 */
class PacObserver
{
    public function saved(Pac $pac): void
    {
        $this->flush($pac->structure_id);
        $this->logStatusTransition($pac);
    }

    public function deleted(Pac $pac): void
    {
        $this->flush($pac->structure_id);

        Log::info('PAC deleted', [
            'pac_id' => $pac->id,
            'structure_id' => $pac->structure_id,
            'status_at_delete' => $pac->status?->value,
        ]);
    }

    private function logStatusTransition(Pac $pac): void
    {
        if (! $pac->wasChanged('status')) {
            return;
        }

        $originalStatus = $pac->getOriginal('status');

        Log::info('PAC status transition', [
            'pac_id' => $pac->id,
            'structure_id' => $pac->structure_id,
            'from' => $originalStatus instanceof \BackedEnum ? $originalStatus->value : $originalStatus,
            'to' => $pac->status?->value,
        ]);
    }

    private function flush(int|string $structureId): void
    {
        Cache::tags(["structure:{$structureId}:dashboard"])->flush();
    }
}
