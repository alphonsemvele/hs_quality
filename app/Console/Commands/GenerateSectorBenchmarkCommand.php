<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\GenerateSectorBenchmarkJob;
use App\Models\User;
use App\Services\SectorBenchmarkService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Dispatches GenerateSectorBenchmarkJob for a given month.
 *
 * Usage:
 *   php artisan benchmark:generate                  # current month
 *   php artisan benchmark:generate --month=2026-04  # specific month
 *   php artisan benchmark:generate --sync           # run synchronously (CI / scripts)
 */
class GenerateSectorBenchmarkCommand extends Command
{
    protected $signature = 'benchmark:generate
        {--month= : Month to snapshot (YYYY-MM, default: current)}
        {--user=  : Platform admin user id to run as (default: first platform admin)}
        {--sync   : Run synchronously instead of queuing}';

    protected $description = 'Generate the monthly sector benchmark snapshot.';

    public function handle(): int
    {
        $monthStr = $this->option('month') ?? now()->format('Y-m');
        $month = Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth();

        $userId = $this->option('user');
        $admin = $userId
            ? User::findOrFail($userId)
            : User::where('is_platform_admin', true)->first();

        if ($admin === null) {
            $this->error('Aucun utilisateur platform admin trouvé. Passez --user=<id>.');

            return self::FAILURE;
        }

        $job = new GenerateSectorBenchmarkJob($month, $admin);

        if ($this->option('sync')) {
            app(SectorBenchmarkService::class)->generateSnapshot($month, $admin);
            $this->info("Snapshot généré pour {$monthStr}.");
        } else {
            dispatch($job);
            $this->info("Job benchmark:{$monthStr} mis en file.");
        }

        return self::SUCCESS;
    }
}
