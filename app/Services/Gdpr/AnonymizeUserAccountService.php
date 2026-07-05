<?php

declare(strict_types=1);

namespace App\Services\Gdpr;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * GDPR (article 17 — right to erasure) anonymisation of a user account.
 *
 * Scrambles PII to a deterministic placeholder, revokes credentials and
 * sessions, and stamps `erased_at` so the rest of the codebase can branch
 * on the User::isErased() helper. Idempotent: re-running on an already
 * erased user is a no-op.
 *
 * Records of activity (interventions performed, incidents declared) are
 * preserved per the Code de la santé publique audit obligations but lose
 * their link to a recognisable identity — once the User row is anonymised,
 * any join from those tables surfaces "compte effacé" instead of a name.
 *
 * Hard-deleting the row is intentionally NOT done here. Soft-delete via
 * `deleted_at` keeps the FK chain intact for the activity records that
 * legally must outlive the user.
 */
class AnonymizeUserAccountService
{
    public function anonymize(User $user): User
    {
        if ($user->isErased()) {
            return $user;
        }

        return DB::transaction(function () use ($user): User {
            $placeholder = sprintf('erased-%s', Str::lower(Str::random(8)));

            $user->forceFill([
                'first_name' => 'Compte',
                'last_name' => 'effacé',
                'email' => $placeholder.'@erased.qualitedomicile.local',
                'phone' => null,
                'avatar' => null,
                'employee_number' => null,
                'specialty' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => null,
                'password' => Hash::make(Str::random(64)),
                'status' => 'erased',
                'erased_at' => now(),
            ])->save();

            // Sanctum tokens — revoke everything so the anonymised
            // identity can't still authenticate via a cached bearer.
            $user->tokens()->delete();

            $user->delete();

            return $user->fresh();
        });
    }
}
