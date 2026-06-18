<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Portal;

use App\Enums\CarePlanStatus;
use App\Http\Controllers\Controller;
use App\Models\CarePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalCarePlanController extends Controller
{
    /**
     * Return the beneficiary's active care plan with its planned tasks.
     *
     * Objectives are encrypted — they are decrypted automatically by
     * Eloquent's cast. The portal shows objectives to the beneficiary
     * (they have the right to access their own care data under RGPD Art 15).
     */
    public function show(Request $request): JsonResponse
    {
        $beneficiary = $request->user()->beneficiary;

        if ($beneficiary === null) {
            return response()->json(['data' => null]);
        }

        $plan = CarePlan::withoutGlobalScopes()
            ->with(['tasks'])
            ->where('beneficiary_id', $beneficiary->id)
            ->where('status', CarePlanStatus::Active->value)
            ->first();

        return response()->json(['data' => $plan]);
    }
}
