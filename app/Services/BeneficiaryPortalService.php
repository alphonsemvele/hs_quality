<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FamilyTokenScope;
use App\Enums\UserType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryFamilyToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Manages portal access for beneficiaries and their family members.
 *
 * Two distinct access models:
 *  1. Beneficiary portal user  — full Sanctum token, owns their data.
 *  2. Family member token      — hashed, scoped, time-limited, no Sanctum.
 *
 * Plain tokens are returned once and never stored — only the SHA-256
 * hash is persisted. Same pattern as Laravel Sanctum personal tokens.
 */
class BeneficiaryPortalService
{
    /**
     * Create (or return existing) portal User account for a beneficiary.
     *
     * Idempotent: calling twice with the same email returns the existing
     * account without resetting the password. The caller must explicitly
     * call resetPortalPassword() to change a credential.
     *
     * The portal user is linked to the beneficiary via the same structure
     * and a `beneficiary_id` column on users (must be present in schema).
     */
    public function provisionAccess(Beneficiary $beneficiary, string $email, string $password): User
    {
        return DB::transaction(function () use ($beneficiary, $email, $password): User {
            $existing = User::withoutGlobalScopes()
                ->where('structure_id', $beneficiary->structure_id)
                ->where('beneficiary_id', $beneficiary->id)
                ->where('type', UserType::BeneficiairePortal->value)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return User::create([
                'structure_id' => $beneficiary->structure_id,
                'beneficiary_id' => $beneficiary->id,
                'first_name' => $beneficiary->first_name,
                'last_name' => $beneficiary->last_name,
                'email' => $email,
                'password' => Hash::make($password),
                'type' => UserType::BeneficiairePortal->value,
            ]);
        });
    }

    /**
     * Issue a new family member access token.
     *
     * @param  list<FamilyTokenScope>  $scopes
     * @return string Plaintext token — show once, then discard.
     */
    public function issueFamilyToken(
        Beneficiary $beneficiary,
        User $issuedBy,
        string $issuedToName,
        array $scopes,
        Carbon $expiresAt,
    ): string {
        $plaintext = Str::random(40);
        $hash = hash('sha256', $plaintext);

        BeneficiaryFamilyToken::create([
            'structure_id' => $beneficiary->structure_id,
            'beneficiary_id' => $beneficiary->id,
            'token_hash' => $hash,
            'scope' => array_map(fn (FamilyTokenScope $s) => $s->value, $scopes),
            'issued_to_name' => $issuedToName,
            'issued_by_user_id' => $issuedBy->id,
            'expires_at' => $expiresAt,
        ]);

        return $plaintext;
    }

    /**
     * Validate a plaintext family token from a request.
     *
     * Returns the token record with its beneficiary relation loaded.
     * Touches `last_used_at` on success so access patterns are auditable.
     *
     * @throws HttpException 401 when invalid/expired.
     */
    public function validateFamilyToken(string $plaintext): BeneficiaryFamilyToken
    {
        $hash = hash('sha256', $plaintext);

        $token = BeneficiaryFamilyToken::withoutGlobalScopes()
            ->with('beneficiary')
            ->where('token_hash', $hash)
            ->first();

        if ($token === null) {
            throw new HttpException(401, 'Jeton d\'accès famille invalide.');
        }

        if ($token->isExpired()) {
            throw new HttpException(401, 'Jeton d\'accès famille expiré.');
        }

        $token->update(['last_used_at' => now()]);

        return $token;
    }

    /**
     * Revoke all family tokens for a beneficiary (e.g. on discharge).
     */
    public function revokeFamilyTokens(Beneficiary $beneficiary): int
    {
        return BeneficiaryFamilyToken::withoutGlobalScopes()
            ->where('beneficiary_id', $beneficiary->id)
            ->delete();
    }
}
