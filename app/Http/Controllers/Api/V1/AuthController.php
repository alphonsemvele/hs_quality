<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        // Block A / #55 — MFA gate for privileged personas at token issuance.
        // Web request access is gated by the RequireMfa middleware (Wave 1 / C3);
        // mobile token issuance must do the same here so a sensitive role
        // can never get a Sanctum token without a valid TOTP code.
        //
        // Respect the same `auth.require_mfa_enrollment` config flag the
        // web-side RequireMfa middleware uses (Wave 1 / C3) so dev/test
        // environments can opt out symmetrically.
        if (config('auth.require_mfa_enrollment', true) && $user->requiresMandatoryMfa()) {
            if ($user->two_factor_confirmed_at === null) {
                return response()->json([
                    'message' => 'Two-factor authentication is required for your role. '
                        .'Enroll via the web app at /user/two-factor-authentication, '
                        .'then retry on mobile.',
                    'code' => 'mfa_enrollment_required',
                ], 423);
            }

            $totpCode = (string) ($request->validated('totp_code') ?? '');

            if ($totpCode === '') {
                return response()->json([
                    'message' => 'TOTP code required. Submit `totp_code` (6 digits) alongside email/password.',
                    'code' => 'mfa_code_required',
                ], 422);
            }

            $secret = decrypt($user->two_factor_secret);
            $valid = app(TwoFactorAuthenticationProvider::class)->verify($secret, $totpCode);

            if (! $valid) {
                return response()->json([
                    'message' => 'Invalid TOTP code.',
                    'code' => 'mfa_code_invalid',
                ], 422);
            }
        }

        // Revoke stale mobile tokens on fresh login to avoid accumulation.
        $user->tokens()->where('name', 'mobile')->delete();

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }
}
