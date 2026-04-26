<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncBatchRequest;
use App\Services\SyncBatchService;
use Illuminate\Http\JsonResponse;

/**
 * Mobile offline-sync surface. The React Native app queues operations
 * locally while offline ("zone blanche") and flushes them here on
 * reconnect.
 *
 * Response is HTTP 207 Multi-Status — the batch as a whole succeeded
 * (validation passed, auth passed, work was attempted) but per-operation
 * outcomes vary. Mobile clients MUST inspect each `results[i].status`:
 *   - "success"  → mark local op synced, replace local state with server_state
 *   - "conflict" → server state diverged (e.g. intervention auto-cancelled);
 *                  surface to user for reconciliation, replace local state
 *   - "rejected" → 403/404; the local op is invalid against server policy
 *                  or the resource no longer exists
 *   - "error"    → unexpected failure; safe to retry the whole batch
 */
class SyncController extends Controller
{
    public function __construct(
        private readonly SyncBatchService $service,
    ) {}

    public function batch(SyncBatchRequest $request): JsonResponse
    {
        $results = $this->service->process(
            $request->validated('operations'),
            $request->user(),
        );

        return response()->json([
            'count' => count($results),
            'results' => $results,
        ], 207); // Multi-Status
    }
}
