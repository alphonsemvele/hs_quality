<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\IndicatorIngestionService;
use Illuminate\Console\Command;

/**
 * Monthly QVCT indicator snapshot. Spec: PHASE2_PROGRESS.md M3.27 +
 * routes/console.php cron entry.
 *
 * Usage:
 *   php artisan qvct:indicators:snapshot
 *
 * Snapshots every structure for the current period (idempotent — if a
 * row exists for this month, the auto-computed columns get refreshed
 * but manual-entry columns are preserved).
 */
class QvctIndicatorSnapshotCommand extends Command
{
    protected $signature = 'qvct:indicators:snapshot';

    protected $description = 'Snapshot QVCT indicators for every structure for the current month.';

    public function handle(IndicatorIngestionService $service): int
    {
        $count = $service->snapshotAllStructures();
        $this->info("Snapshotted QVCT indicators for {$count} structure(s).");

        return self::SUCCESS;
    }
}
