<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QvctExchangeRequest;
use App\Models\User;

/**
 * Three legitimate access paths to a request:
 *   1. The requester (always — outgoing visibility)
 *   2. Any user holding the addressee_role's permission (incoming
 *      visibility for triage; multiple RH users may share the queue)
 *   3. The user who accepted it (carry-over visibility once owned)
 *
 * Other users — even within the same tenant — see nothing.
 */
class QvctExchangeRequestPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('qvct.request_rh_exchange')
            || $user->hasPermissionTo('qvct.alert.receive')
            || $user->hasPermissionTo('qvct.view.team_aggregates');
    }

    public function view(User $user, QvctExchangeRequest $request): bool
    {
        if ($request->requester_id === $user->id) {
            return true;
        }

        if ($request->accepted_by === $user->id) {
            return true;
        }

        return $user->hasPermissionTo($request->addressee_role->addresseePermission());
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('qvct.request_rh_exchange');
    }

    public function accept(User $user, QvctExchangeRequest $request): bool
    {
        if ($request->isClosed()) {
            return false;
        }

        return $user->hasPermissionTo($request->addressee_role->addresseePermission());
    }

    public function schedule(User $user, QvctExchangeRequest $request): bool
    {
        if ($request->isClosed()) {
            return false;
        }

        return $user->hasPermissionTo($request->addressee_role->addresseePermission());
    }

    public function close(User $user, QvctExchangeRequest $request): bool
    {
        if ($request->isClosed()) {
            return false;
        }

        // Either side can close — the requester may withdraw the ask, the
        // addressee may close after the meeting.
        if ($request->requester_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo($request->addressee_role->addresseePermission());
    }
}
