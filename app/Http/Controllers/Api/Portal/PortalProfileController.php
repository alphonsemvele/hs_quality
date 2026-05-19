<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalProfileController extends Controller
{
    /** Return the authenticated beneficiary's basic profile. */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $beneficiary = $user->beneficiary;

        return response()->json([
            'id' => (string) $user->id,
            'name' => $user->fullName(),
            'beneficiary' => $beneficiary ? [
                'id' => $beneficiary->id,
                'first_name' => $beneficiary->first_name,
                'last_name' => $beneficiary->last_name,
                'gir' => (string) $beneficiary->gir,
            ] : null,
        ]);
    }
}
