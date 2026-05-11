<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CertificationExpiryAlertJob;
use Illuminate\Console\Command;

/**
 * Daily certification expiry sweep. Spec: PHASE2_PROGRESS.md M5.11.
 *
 * Cron schedule: routes/console.php → daily 03:00 Europe/Paris.
 *
 * Usage:
 *   php artisan certifications:expiry-sweep
 *
 * Dispatches a single CertificationExpiryAlertJob; the job iterates
 * every cert via chunkById and updates only those whose alert window
 * has advanced. Idempotent — safe to re-run any number of times.
 */
class CertificationExpirySweepCommand extends Command
{
    protected $signature = 'certifications:expiry-sweep';

    protected $description = 'Sweep certifications and fire expiry alerts at T-90 / T-30 / T-7 / expired windows.';

    public function handle(): int
    {
        CertificationExpiryAlertJob::dispatchSync();

        $this->info('Certification expiry sweep dispatched.');

        return self::SUCCESS;
    }
}
