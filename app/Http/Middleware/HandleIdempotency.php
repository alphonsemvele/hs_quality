<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotency layer for mutation endpoints.
 *
 * If the request carries an `Idempotency-Key` header, the first response is
 * stored in Redis for 24h. Subsequent requests with the same key (from the
 * same user) receive the stored response immediately, preventing duplicate
 * declarations/check-ins when mobile clients retry over flaky connections.
 */
class HandleIdempotency
{
    private const TTL = 86400; // 24 hours

    public function handle(Request $request, Closure $next): Response
    {
        // Only applies to state-mutating methods.
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');

        if (! $key || strlen($key) > 128) {
            return $next($request);
        }

        $userId = $request->user()?->id ?? 'guest';
        $cacheKey = "idempotency:{$userId}:{$key}";

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            return response()->json(
                $cached['body'],
                $cached['status'],
                array_merge($cached['headers'], ['X-Idempotent-Replayed' => 'true']),
            );
        }

        $response = $next($request);

        // Only cache successful responses (2xx) — errors should be retried.
        if ($response->getStatusCode() < 300) {
            Cache::put($cacheKey, [
                'status' => $response->getStatusCode(),
                'headers' => [],
                'body' => json_decode($response->getContent(), true) ?? [],
            ], self::TTL);
        }

        return $response;
    }
}
