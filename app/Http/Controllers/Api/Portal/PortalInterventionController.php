<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\Intervention;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalInterventionController extends Controller
{
    /**
     * Return the beneficiary's recent intervention history.
     *
     * Only date, status, and intervenant first-name are exposed — the
     * full medical report is NOT surfaced here (it is encrypted at rest
     * and scoped to coordinateurs/dirigeants in the admin app).
     */
    public function index(Request $request): JsonResponse
    {
        $beneficiary = $request->user()->beneficiary;

        if ($beneficiary === null) {
            return response()->json(['data' => []]);
        }

        $interventions = Intervention::withoutGlobalScopes()
            ->with(['intervenant:id,name'])
            ->select(['id', 'planned_date', 'actual_start_at', 'actual_end_at', 'status', 'intervenant_id'])
            ->where('beneficiary_id', $beneficiary->id)
            ->orderByDesc('planned_date')
            ->limit(50)
            ->get();

        return response()->json(['data' => $interventions]);
    }
}
