<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Portal;

use App\Enums\FamilyTokenScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\FamilySatisfactionRequest;
use App\Models\BeneficiaryFamilyToken;
use App\Models\BeneficiarySatisfactionRating;
use App\Models\CarePlan;
use App\Models\Intervention;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only endpoints for family members accessing via a scoped token.
 * Scope is enforced per-method before any data is returned.
 */
class FamilyPortalController extends Controller
{
    public function carePlan(Request $request): JsonResponse
    {
        /** @var BeneficiaryFamilyToken $token */
        $token = $request->attributes->get('family_token');
        $this->requireScope($token, FamilyTokenScope::ReadPlan);

        $plan = CarePlan::withoutGlobalScopes()
            ->with(['tasks'])
            ->where('beneficiary_id', $token->beneficiary_id)
            ->where('status', 'active')
            ->first();

        return response()->json(['data' => $plan]);
    }

    public function interventions(Request $request): JsonResponse
    {
        /** @var BeneficiaryFamilyToken $token */
        $token = $request->attributes->get('family_token');
        $this->requireScope($token, FamilyTokenScope::ReadInterventions);

        $interventions = Intervention::withoutGlobalScopes()
            ->with(['intervenant:id,name'])
            ->select(['id', 'planned_date', 'actual_start_at', 'actual_end_at', 'status', 'intervenant_id'])
            ->where('beneficiary_id', $token->beneficiary_id)
            ->orderByDesc('planned_date')
            ->limit(30)
            ->get();

        return response()->json(['data' => $interventions]);
    }

    public function submitSatisfaction(FamilySatisfactionRequest $request): JsonResponse
    {
        /** @var BeneficiaryFamilyToken $token */
        $token = $request->attributes->get('family_token');
        $this->requireScope($token, FamilyTokenScope::SubmitSatisfaction);

        $beneficiary = $token->beneficiary;

        $rating = BeneficiarySatisfactionRating::create([
            'structure_id' => $token->structure_id,
            'beneficiary_id' => $beneficiary->id,
            'intervention_id' => $request->validated('intervention_id'),
            'score' => $request->validated('score'),
            'comment' => $request->validated('comment'),
            'rated_at' => now()->toDateString(),
            'rated_by' => $token->issued_by_user_id,
        ]);

        return response()->json($rating, 201);
    }

    private function requireScope(BeneficiaryFamilyToken $token, FamilyTokenScope $scope): void
    {
        if (! $token->hasScope($scope)) {
            abort(403, "Ce jeton ne dispose pas de la permission '{$scope->value}'.");
        }
    }
}
