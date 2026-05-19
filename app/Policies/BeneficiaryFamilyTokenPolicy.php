<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Beneficiary;
use App\Models\BeneficiaryFamilyToken;
use App\Models\User;

class BeneficiaryFamilyTokenPolicy extends BasePolicy
{
    /** Only coordinateurs and dirigeants can manage family access tokens. */
    public function create(User $user, Beneficiary $beneficiary): bool
    {
        return $user->hasAnyRole(['coordinateur', 'dirigeant'])
            && $user->structure_id === $beneficiary->structure_id;
    }

    public function revoke(User $user, BeneficiaryFamilyToken $token): bool
    {
        return $user->hasAnyRole(['coordinateur', 'dirigeant']);
    }

    public function viewAny(User $user, Beneficiary $beneficiary): bool
    {
        return $user->hasAnyRole(['coordinateur', 'dirigeant'])
            && $user->structure_id === $beneficiary->structure_id;
    }
}
