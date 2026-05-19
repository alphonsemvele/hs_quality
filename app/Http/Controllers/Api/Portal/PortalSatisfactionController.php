<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\SubmitPortalSatisfactionRequest;
use App\Models\BeneficiarySatisfactionRating;
use Illuminate\Http\JsonResponse;

class PortalSatisfactionController extends Controller
{
    /**
     * Submit a satisfaction rating from the beneficiary themselves.
     *
     * Reuses BeneficiarySatisfactionRating — same model, `rated_by` is
     * set to the portal user's id so the admin app can distinguish
     * beneficiary-submitted vs coordinator-submitted ratings.
     */
    public function store(SubmitPortalSatisfactionRequest $request): JsonResponse
    {
        $user = $request->user();
        $beneficiary = $user->beneficiary;

        if ($beneficiary === null) {
            abort(422, 'Aucun bénéficiaire lié à ce compte portail.');
        }

        $rating = BeneficiarySatisfactionRating::create([
            'structure_id' => $beneficiary->structure_id,
            'beneficiary_id' => $beneficiary->id,
            'intervention_id' => $request->validated('intervention_id'),
            'score' => $request->validated('score'),
            'comment' => $request->validated('comment'),
            'rated_at' => now()->toDateString(),
            'rated_by' => $user->id,
        ]);

        return response()->json($rating, 201);
    }
}
