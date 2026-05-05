<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StructureTier;
use App\Models\Structure;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionBuilder;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * BillingService — Stripe glue isolated from the controller. Handles
 * subscription creation, tier upgrades, cancel, and the trial-window
 * computation. Stays small: Cashier already does the heavy lifting,
 * this layer just routes spec language ("tier" / "trial_days") to
 * Cashier's API surface.
 *
 * Spec: PHASE2_PROGRESS.md C1.
 */
class BillingService
{
    public const SUBSCRIPTION_TYPE = 'default';

    /**
     * Subscribe a structure to a tier with the given Stripe payment
     * method. Starts a free trial of length `config('billing.trial_days')`.
     *
     * Throws 422 if the requested tier has no price ID configured for
     * the current environment — better to fail loudly than to create
     * a Stripe subscription against a missing price.
     */
    public function subscribe(Structure $structure, StructureTier $tier, string $paymentMethodId): Subscription
    {
        $priceId = $this->priceFor($tier);

        $builder = $structure->newSubscription(self::SUBSCRIPTION_TYPE, $priceId);

        $trialDays = (int) config('billing.trial_days', 30);
        if ($trialDays > 0) {
            $builder->trialDays($trialDays);
        }

        $subscription = $builder->create($paymentMethodId);

        // Mirror the tier on the structure so hasFeature() reflects the
        // new gate immediately. Cashier doesn't know about our tier
        // enum — we keep both in sync from this single write site.
        $structure->update(['tier' => $tier]);

        return $subscription;
    }

    /**
     * Return the Stripe price ID configured for a tier. Throws if the
     * env hasn't been wired (e.g. running in a fresh dev DB without
     * BILLING_PRICE_* set).
     */
    public function priceFor(StructureTier $tier): string
    {
        $price = config('billing.prices.'.$tier->value);

        if (! is_string($price) || $price === '') {
            throw new HttpException(422, sprintf(
                'No Stripe price configured for tier "%s" — set BILLING_PRICE_%s in env.',
                $tier->value,
                strtoupper($tier->value),
            ));
        }

        return $price;
    }

    /**
     * @return SubscriptionBuilder used for unit testing — exposes
     *                             the underlying Cashier builder so tests can inspect trial /
     *                             price configuration without hitting Stripe.
     */
    public function builder(Structure $structure, StructureTier $tier): SubscriptionBuilder
    {
        return $structure->newSubscription(self::SUBSCRIPTION_TYPE, $this->priceFor($tier))
            ->trialDays((int) config('billing.trial_days', 30));
    }
}
