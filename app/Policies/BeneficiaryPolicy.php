<?php

namespace App\Policies;

use App\Models\Beneficiary;
use App\Models\User;

/**
 * Who can do what with a Beneficiary.
 *
 * Row-level tenancy check runs first in BasePolicy::before() — if the
 * beneficiary isn't in the user's structure, access is denied regardless
 * of role. Methods below layer role-specific rules on top.
 *
 * See: references/rbac/matrix.md (M1 row)
 */
class BeneficiaryPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'beneficiaries.view.assigned',
            'beneficiaries.view.structure',
        ]);
    }

    public function view(User $user, Beneficiary $beneficiary): bool
    {
        if ($user->hasPermissionTo('beneficiaries.view.structure')) {
            return true;
        }

        // Intervenants can only see beneficiaries they're assigned to. The
        // intervenant_beneficiary pivot comes in Phase 1 Week 4; until then,
        // assigned-only access falls back to "no structure-wide permission = deny".
        return $user->hasPermissionTo('beneficiaries.view.assigned')
            && $this->isAssignedTo($user, $beneficiary);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('beneficiaries.create');
    }

    public function update(User $user, Beneficiary $beneficiary): bool
    {
        if ($beneficiary->isErased()) {
            return false;
        }

        return $user->hasPermissionTo('beneficiaries.update');
    }

    public function delete(User $user, Beneficiary $beneficiary): bool
    {
        return $user->hasPermissionTo('beneficiaries.delete');
    }

    public function restore(User $user, Beneficiary $beneficiary): bool
    {
        return $user->hasRole('dirigeant');
    }

    /**
     * Trigger RGPD Art 17 erasure. Irreversible — always step-up MFA gated
     * at the route level (see references/compliance/mfa-requirements.md).
     */
    public function erase(User $user, Beneficiary $beneficiary): bool
    {
        return $user->hasPermissionTo('rgpd.erasure.execute');
    }

    /**
     * Intervenant assignment check. Checks for an ACTIVE assignment
     * (unassigned_at IS NULL) linking this user to this beneficiary.
     *
     * Implemented by the intervenant_assignments table added in
     * Phase 1 Week 4. Queries via the exists() pattern to avoid
     * loading a full collection when we only need a boolean.
     */
    private function isAssignedTo(User $user, Beneficiary $beneficiary): bool
    {
        return \App\Models\IntervenantAssignment::query()
            ->active()
            ->where('user_id', $user->id)
            ->where('beneficiary_id', $beneficiary->id)
            ->exists();
    }
}
