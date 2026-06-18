<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\SectorBenchmarkService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Monthly async job: generate the sector benchmark snapshot.
 *
 * Scheduled via routes/console.php. Can also be dispatched on-demand
 * by a platform admin through the API.
 *
 * Tenant context: this job intentionally crosses tenants — it runs
 * as a platform-admin user with 'cross_tenant_benchmark.read'.
 * The audit trail is embedded in CrossTenantQueryService.
 */
class GenerateSectorBenchmarkJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 300; // 5 min between retries

    public function __construct(
        public readonly Carbon $month,
        public readonly User $triggeredBy,
    ) {}

    public function handle(SectorBenchmarkService $service): void
    {
        Log::info('Generating sector benchmark snapshot', [
            'month' => $this->month->toDateString(),
            'triggered_by' => $this->triggeredBy->id,
        ]);

        $service->generateSnapshot($this->month, $this->triggeredBy);
    }
}
