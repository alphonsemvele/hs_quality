<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\InterventionService;
use Illuminate\Console\Command;

/**
 * Sweep planned interventions whose scheduled end time + grace window has
 * passed without check-in, flag them as `missed`.
 *
 * Scheduled in routes/console.php to run every 15 minutes. Idempotent —
 * an already-missed intervention is a no-op (status guard in service).
 *
 *   php artisan interventions:sweep-missed
 *   php artisan interventions:sweep-missed --grace=60   # one-hour grace
 */
class SweepMissedInterventionsCommand extends Command
{
    protected $signature = 'interventions:sweep-missed
        {--grace=30 : Minutes after planned_end_time before sweeping}';

    protected $description = 'Mark stale planned interventions as missed (cron).';

    public function handle(InterventionService $service): int
    {
        $grace = (int) $this->option('grace');

        $count = $service->sweepMissed(graceMinutes: $grace);

        $this->components->info(sprintf(
            'Swept %d stale intervention%s as missed (grace=%dmin).',
            $count,
            $count === 1 ? '' : 's',
            $grace,
        ));

        return self::SUCCESS;
    }
}
