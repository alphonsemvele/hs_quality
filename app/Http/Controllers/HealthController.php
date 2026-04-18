<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Health check endpoints for load balancers and orchestrators.
 *
 *   GET /health/live   — liveness probe. Succeeds if the PHP process responds.
 *                        Used by the ALB/ECS to decide "is this instance alive?"
 *
 *   GET /health/ready  — readiness probe. Succeeds only when DB + Redis + S3
 *                        are reachable. Used by load balancer to decide
 *                        "should this instance receive new traffic?"
 *
 * Both endpoints are unauthenticated and bypass the tenant resolver.
 */
class HealthController extends Controller
{
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'app' => config('app.name'),
            'env' => config('app.env'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
        ];

        $allOk = collect($checks)->every(fn (array $c) => $c['ok']);

        return response()->json([
            'status' => $allOk ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $allOk ? 200 : 503);
    }

    /** @return array{ok: bool, message?: string} */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'database unreachable'];
        }
    }

    /** @return array{ok: bool, message?: string} */
    private function checkRedis(): array
    {
        try {
            $pong = (string) Redis::connection()->command('ping');

            return ['ok' => $pong === 'PONG' || $pong === '+PONG'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'redis unreachable'];
        }
    }

    /** @return array{ok: bool, message?: string} */
    private function checkStorage(): array
    {
        try {
            // Don't fail the readiness check if S3 is misconfigured in local dev
            // with no bucket; we just verify the disk driver can respond.
            Storage::disk(config('filesystems.default'))->exists('health-check-probe');

            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'storage unreachable'];
        }
    }
}
