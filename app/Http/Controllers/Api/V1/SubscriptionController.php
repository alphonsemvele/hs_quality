<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\StructureTier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\SubscribeStructureRequest;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;

/**
 * Subscription endpoints — `/api/v1/billing/*`. Mobile and the
 * Inertia frontend both call into this. Each request resolves the
 * tenant via TenantResolver (current_structure container binding),
 * so the controller never accepts a structure ID in the URL — we
 * always operate on the caller's own structure.
 *
 * Spec: PHASE2_PROGRESS.md C1.
 */
class SubscriptionController extends Controller
{
    public function __construct(private readonly BillingService $billing) {}

    public function subscribe(SubscribeStructureRequest $request): JsonResponse
    {
        $structure = currentStructure();

        $subscription = $this->billing->subscribe(
            $structure,
            StructureTier::from($request->validated('tier')),
            $request->validated('payment_method_id'),
        );

        return response()->json([
            'subscription' => [
                'id' => $subscription->id,
                'stripe_id' => $subscription->stripe_id,
                'stripe_status' => $subscription->stripe_status,
                'stripe_price' => $subscription->stripe_price,
                'quantity' => $subscription->quantity,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ],
            'structure' => [
                'id' => $structure->id,
                'tier' => $structure->fresh()->tier->value,
            ],
        ], 201);
    }

    public function show(): JsonResponse
    {
        $structure = currentStructure();

        $subscription = $structure->subscription(BillingService::SUBSCRIPTION_TYPE);

        return response()->json([
            'subscribed' => $subscription !== null,
            'on_trial' => $structure->onTrial(),
            'tier' => $structure->tier->value,
            'subscription' => $subscription === null ? null : [
                'stripe_status' => $subscription->stripe_status,
                'stripe_price' => $subscription->stripe_price,
                'quantity' => $subscription->quantity,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ],
        ]);
    }
}
