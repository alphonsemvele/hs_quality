<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\QvctMood;
use App\Http\Controllers\Controller;
use App\Http\Requests\Qvct\StoreJournalEntryRequest;
use App\Models\QvctJournalEntry;
use App\Services\JournalEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QvctJournalController extends Controller
{
    public function __construct(private readonly JournalEntryService $service) {}

    public function store(StoreJournalEntryRequest $request): JsonResponse
    {
        $entry = $this->service->write(
            $request->user(),
            $request->validated('body'),
            QvctMood::from($request->validated('mood')),
            (bool) $request->validated('shared_with_rh', false),
        );

        return response()->json($entry, 201);
    }

    /**
     * Mine — caller's own entries only. The service scopes by user_id;
     * even a coordinateur calling this endpoint sees only their own
     * journal, never anyone else's. RH-shared visibility goes through
     * the separate `sharedWithRh` endpoint with a different gate.
     */
    public function mine(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QvctJournalEntry::class);

        return response()->json([
            'data' => $this->service->listForUser($request->user()),
        ]);
    }

    public function sharedWithRh(Request $request): JsonResponse
    {
        // Explicit gate — only users with both qvct.alert.receive AND
        // qvct.weak_signal.acknowledge see the RH-shared queue (matches
        // QvctJournalEntryPolicy::view).
        abort_unless(
            $request->user()->hasPermissionTo('qvct.alert.receive')
                && $request->user()->hasPermissionTo('qvct.weak_signal.acknowledge'),
            403,
            'RH access only.',
        );

        return response()->json([
            'data' => $this->service->listSharedForRh(),
        ]);
    }
}
