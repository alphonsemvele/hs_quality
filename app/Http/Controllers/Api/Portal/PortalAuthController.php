<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Portal;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\PortalLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalAuthController extends Controller
{
    /**
     * Authenticate a beneficiary and return a Sanctum token.
     *
     * Validates that the user is of type BeneficiairePortal so a staff
     * member cannot accidentally log in through the portal endpoint.
     */
    public function login(PortalLoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->type !== UserType::BeneficiairePortal) {
            Auth::logout();

            return response()->json(['message' => 'Accès non autorisé via le portail.'], 403);
        }

        $token = $user->createToken('portal')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }
}
