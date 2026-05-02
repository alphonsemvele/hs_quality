<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QvctWeakSignal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RH-facing endpoint for triaging weak signals. Read-only listing +
 * acknowledge action. Intervenants never reach this controller — the
 * QvctWeakSignalPolicy::viewAny check (qvct.alert.receive permission)
 * keeps it RH-only.
 */
class QvctWeakSignalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QvctWeakSignal::class);

        $query = QvctWeakSignal::query()
            ->with(['campaign:id,title,closes_at'])
            ->orderByDesc('severity')
            ->orderByDesc('created_at');

        if ($request->input('outstanding', 'true') === 'true') {
            $query->whereNull('acknowledged_at');
        }

        return response()->json([
            'data' => $query->paginate(50),
        ]);
    }

    public function acknowledge(QvctWeakSignal $signal): JsonResponse
    {
        $this->authorize('acknowledge', $signal);

        $signal->update([
            'acknowledged_by' => request()->user()->id,
            'acknowledged_at' => now(),
        ]);

        return response()->json($signal->fresh());
    }
}
