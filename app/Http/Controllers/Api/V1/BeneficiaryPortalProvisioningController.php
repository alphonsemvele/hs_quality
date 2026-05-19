<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\IssueFamilyTokenRequest;
use App\Http\Requests\Portal\ProvisionPortalAccessRequest;
use App\Models\Beneficiary;
use App\Models\BeneficiaryFamilyToken;
use App\Services\BeneficiaryPortalService;
use Illuminate\Http\JsonResponse;

/**
 * Admin-side endpoints for managing beneficiary portal access.
 * Consumed by coordinateurs and dirigeants from the admin web/mobile app.
 */
class BeneficiaryPortalProvisioningController extends Controller
{
    public function __construct(private readonly BeneficiaryPortalService $portalService) {}

    /**
     * Provision (or return existing) portal account for a beneficiary.
     */
    public function provision(ProvisionPortalAccessRequest $request, Beneficiary $beneficiary): JsonResponse
    {
        $this->authorize('update', $beneficiary);

        $user = $this->portalService->provisionAccess(
            $beneficiary,
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json([
            'user_id' => $user->id,
            'email' => $user->email,
            'created' => $user->wasRecentlyCreated,
        ], $user->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Issue a new family member access token.
     *
     * Returns the plaintext token exactly once — it cannot be retrieved
     * again. The structure should communicate it to the family member
     * securely (e.g. printed letter, SMS from their own number).
     */
    public function issueFamilyToken(IssueFamilyTokenRequest $request, Beneficiary $beneficiary): JsonResponse
    {
        $this->authorize('update', $beneficiary);

        $scopes = $request->validatedScopes();

        $plaintext = $this->portalService->issueFamilyToken(
            beneficiary: $beneficiary,
            issuedBy: $request->user(),
            issuedToName: $request->validated('issued_to_name'),
            scopes: $scopes,
            expiresAt: $request->validatedExpiresAt(),
        );

        return response()->json([
            'token' => $plaintext,
            'expires_at' => $request->validatedExpiresAt()->toIso8601String(),
            'scope' => array_map(fn ($s) => $s->value, $scopes),
            'message' => 'Conservez ce jeton — il ne sera plus affiché.',
        ], 201);
    }

    /**
     * List all active family tokens for a beneficiary.
     */
    public function listFamilyTokens(Beneficiary $beneficiary): JsonResponse
    {
        $this->authorize('update', $beneficiary);

        $tokens = BeneficiaryFamilyToken::withoutGlobalScopes()
            ->where('beneficiary_id', $beneficiary->id)
            ->where('expires_at', '>', now())
            ->select(['id', 'issued_to_name', 'scope', 'expires_at', 'last_used_at', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $tokens]);
    }

    /**
     * Revoke a specific family token.
     */
    public function revokeFamilyToken(Beneficiary $beneficiary, BeneficiaryFamilyToken $token): JsonResponse
    {
        $this->authorize('update', $beneficiary);

        $token->delete();

        return response()->json(['message' => 'Jeton révoqué.']);
    }
}
