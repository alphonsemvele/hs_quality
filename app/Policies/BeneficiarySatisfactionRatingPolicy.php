<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BeneficiarySatisfactionRating;
use App\Models\User;

/**
 * Satisfaction ratings inherit the beneficiary access matrix:
 *   - view: anyone with beneficiaries.view.structure
 *   - create / delete: anyone with beneficiaries.update
 * No update path (immutable once recorded — Phase 3 will introduce
 * supersedes-rating relationships for corrections).
 */
class BeneficiarySatisfactionRatingPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('beneficiaries.view.structure')
            || $user->hasPermissionTo('beneficiaries.view.assigned');
    }

    public function view(User $user, BeneficiarySatisfactionRating $rating): bool
    {
        return $user->hasPermissionTo('beneficiaries.view.structure')
            || $user->hasPermissionTo('beneficiaries.view.assigned');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('beneficiaries.update');
    }

    public function delete(User $user, BeneficiarySatisfactionRating $rating): bool
    {
        return $user->hasPermissionTo('beneficiaries.update');
    }
}
