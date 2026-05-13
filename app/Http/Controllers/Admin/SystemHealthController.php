<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Super-admin infrastructure dashboard. Surfaces queue depth, Redis status,
 * database latency and failed-job count so platform operators can spot
 * production issues without leaving the Inertia admin shell.
 *
 * Endpoint is intentionally cheap to call (sub-100ms target) so an operator
 * can refresh repeatedly without overloading the cluster.
 */
class SystemHealthController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/system-health/index', [
            'snapshot' => [
                'collected_at' => CarbonImmutable::now()->toIso8601String(),
                'queue' => $this->queueMetrics(),
                'database' => $this->databaseMetrics(),
                'redis' => $this->redisMetrics(),
                'failed_jobs' => $this->failedJobsMetrics(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function queueMetrics(): array
    {
        try {
            $depth = (int) Redis::connection('default')->llen('queues:default');
            $highPriority = (int) Redis::connection('default')->llen('queues:high');

            return [
                'driver' => (string) config('queue.default'),
                'default_depth' => $depth,
                'high_depth' => $highPriority,
                'status' => $depth > 500 ? 'warning' : ($depth > 2000 ? 'danger' : 'healthy'),
                'error' => null,
            ];
        } catch (Throwable $e) {
            Log::warning('SystemHealth queue metrics failed', ['exception' => $e->getMessage()]);

            return [
                'driver' => (string) config('queue.default'),
                'default_depth' => null,
                'high_depth' => null,
                'status' => 'unknown',
                'error' => 'Impossible de lire la file Redis.',
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseMetrics(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->select('SELECT 1 AS ok');
            $latencyMs = (int) round((microtime(true) - $start) * 1000);

            return [
                'driver' => (string) config('database.default'),
                'latency_ms' => $latencyMs,
                'status' => $latencyMs > 200 ? 'warning' : 'healthy',
                'error' => null,
            ];
        } catch (Throwable $e) {
            Log::warning('SystemHealth database metrics failed', ['exception' => $e->getMessage()]);

            return [
                'driver' => (string) config('database.default'),
                'latency_ms' => null,
                'status' => 'danger',
                'error' => 'Connexion base de données indisponible.',
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function redisMetrics(): array
    {
        try {
            $start = microtime(true);
            $pong = Redis::connection()->ping();
            $latencyMs = (int) round((microtime(true) - $start) * 1000);

            return [
                'reachable' => true,
                'latency_ms' => $latencyMs,
                'ping' => is_string($pong) ? $pong : 'PONG',
                'status' => $latencyMs > 100 ? 'warning' : 'healthy',
                'error' => null,
            ];
        } catch (Throwable $e) {
            Log::warning('SystemHealth Redis metrics failed', ['exception' => $e->getMessage()]);

            return [
                'reachable' => false,
                'latency_ms' => null,
                'ping' => null,
                'status' => 'danger',
                'error' => 'Redis injoignable.',
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function failedJobsMetrics(): array
    {
        try {
            $last24h = (int) DB::table('failed_jobs')
                ->where('failed_at', '>=', CarbonImmutable::now()->subDay())
                ->count();
            $total = (int) DB::table('failed_jobs')->count();
            $recent = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(5)
                ->get(['id', 'connection', 'queue', 'exception', 'failed_at'])
                ->map(fn (object $row): array => [
                    'id' => (int) $row->id,
                    'connection' => (string) $row->connection,
                    'queue' => (string) $row->queue,
                    'exception_class' => $this->firstLineOfException((string) $row->exception),
                    'failed_at' => (string) $row->failed_at,
                ])
                ->all();

            return [
                'total' => $total,
                'last_24h' => $last24h,
                'recent' => $recent,
                'status' => $last24h > 10 ? 'danger' : ($last24h > 0 ? 'warning' : 'healthy'),
                'error' => null,
            ];
        } catch (Throwable $e) {
            Log::warning('SystemHealth failed-jobs metrics failed', ['exception' => $e->getMessage()]);

            return [
                'total' => null,
                'last_24h' => null,
                'recent' => [],
                'status' => 'unknown',
                'error' => 'Table failed_jobs inaccessible.',
            ];
        }
    }

    private function firstLineOfException(string $stack): string
    {
        $line = strtok($stack, "\n");

        return is_string($line) ? trim($line) : '';
    }
}
