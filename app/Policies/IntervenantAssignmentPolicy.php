<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IntervenantAssignment;
use App\Models\User;

/**
 * Authorisation for the flat assignment routes (DELETE /assignments/{id}).
 *
 * Tenant ownership is enforced by BasePolicy::before() — an assignment
 * row from another tenant short-circuits to deny before any method here
 * runs. Method-level checks layer the role permission on top.
 *
 * Creation is authorised separately via BeneficiaryPolicy::update on the
 * route's parent beneficiary, since attach is conceptually a mutation
 * of the beneficiary's care team.
 */
class IntervenantAssignmentPolicy extends BasePolicy
{
    public function delete(User $user, IntervenantAssignment $assignment): bool
    {
        return $user->hasPermissionTo('beneficiaries.update');
    }
}
