<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AccountDeletionRequest;
use App\Models\User;

/**
 * RGPD (art. 17) self-service policy. An account-deletion request belongs
 * to exactly one user exercising their right to erasure, so every ability
 * keys off ownership (`user_id === $user->id`) rather than a role.
 *
 * Tenant isolation is enforced one layer beneath this by
 * {@see BasePolicy::before()}, which denies any cross-structure access
 * before these methods run.
 */
class AccountDeletionRequestPolicy extends BasePolicy
{
    /**
     * Any authenticated user may see their deletion status — the controller
     * scopes the lookup to their own request by user_id.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AccountDeletionRequest $request): bool
    {
        return $request->user_id === $user->id;
    }

    /**
     * Every authenticated user may request erasure of their own account.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * The owner may attempt to cancel their own request. Whether the 30-day
     * cooling-off window is still open is a business-state check the
     * controller performs separately (see
     * {@see AccountDeletionRequest::isCancellable()}) so it can return a
     * "délai dépassé" message rather than a bare 403.
     */
    public function cancel(User $user, AccountDeletionRequest $request): bool
    {
        return $request->user_id === $user->id;
    }
}
