<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QvctExchangeAddresseeRole;
use App\Enums\QvctExchangeStatus;
use App\Models\QvctExchangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Lifecycle: pending → accepted → scheduled → closed.
 * Spec: PHASE2_PROGRESS.md M3.25 + CDC §M3 line "Secure exchange
 * request tool with manager or référent RH".
 *
 * State invariants enforced here (not in the policy — policy decides
 * "may", service decides "is the lifecycle position legal"):
 *   - cannot accept a closed request
 *   - cannot schedule a request that hasn't been accepted (or is already
 *     closed)
 *   - cannot close twice
 */
class ExchangeRequestService
{
    public function create(User $requester, QvctExchangeAddresseeRole $addressee, ?string $message = null): QvctExchangeRequest
    {
        return QvctExchangeRequest::create([
            'structure_id' => $requester->structure_id,
            'requester_id' => $requester->id,
            'addressee_role' => $addressee->value,
            'status' => QvctExchangeStatus::Pending->value,
            'message' => $message,
        ]);
    }

    public function accept(QvctExchangeRequest $request, User $accepter): QvctExchangeRequest
    {
        if ($request->isClosed()) {
            throw new HttpException(409, 'Cannot accept a closed exchange request.');
        }

        $request->update([
            'status' => QvctExchangeStatus::Accepted->value,
            'accepted_by' => $accepter->id,
            'accepted_at' => now(),
        ]);

        return $request->fresh();
    }

    public function schedule(QvctExchangeRequest $request, Carbon $when): QvctExchangeRequest
    {
        if ($request->isClosed()) {
            throw new HttpException(409, 'Cannot schedule a closed exchange request.');
        }

        if ($request->status === QvctExchangeStatus::Pending) {
            throw new HttpException(409, 'Accept the request before scheduling a meeting.');
        }

        $request->update([
            'status' => QvctExchangeStatus::Scheduled->value,
            'scheduled_at' => $when,
        ]);

        return $request->fresh();
    }

    public function close(QvctExchangeRequest $request, ?string $reason = null): QvctExchangeRequest
    {
        if ($request->isClosed()) {
            throw new HttpException(409, 'Exchange request is already closed.');
        }

        $request->update([
            'status' => QvctExchangeStatus::Closed->value,
            'closed_at' => now(),
            'closed_reason' => $reason,
        ]);

        return $request->fresh();
    }

    /**
     * Outgoing — what THIS user has requested.
     *
     * @return Collection<int, QvctExchangeRequest>
     */
    public function outgoingFor(User $user): Collection
    {
        return QvctExchangeRequest::query()
            ->where('requester_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Incoming — pending or in-progress requests routed to the addressee
     * role(s) the user holds permission for. Same user can have both
     * RH and Manager permissions; both queues are merged.
     *
     * @return Collection<int, QvctExchangeRequest>
     */
    public function incomingFor(User $user): Collection
    {
        $roles = collect(QvctExchangeAddresseeRole::cases())
            ->filter(fn (QvctExchangeAddresseeRole $role) => $user->hasPermissionTo($role->addresseePermission()))
            ->map(fn (QvctExchangeAddresseeRole $role) => $role->value)
            ->all();

        if ($roles === []) {
            return new Collection;
        }

        return QvctExchangeRequest::query()
            ->whereIn('addressee_role', $roles)
            ->whereNotIn('status', [QvctExchangeStatus::Closed->value])
            ->orderBy('created_at')
            ->get();
    }
}
