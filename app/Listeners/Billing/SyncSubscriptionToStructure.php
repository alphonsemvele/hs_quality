<?php

declare(strict_types=1);

namespace App\Listeners\Billing;

use App\Enums\StructureTier;
use App\Models\Structure;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Mirrors Stripe subscription state back to structures.tier so that
 * hasFeature() stays consistent with the Cashier subscription record.
 *
 * Stripe retries failed webhook deliveries, so every branch must be
 * idempotent — writing the same value twice produces the same result.
 *
 * Spec: PHASE2_PROGRESS.md C2.
 */
class SyncSubscriptionToStructure
{
    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? '';
        $data = $event->payload['data']['object'] ?? [];

        match ($type) {
            'customer.subscription.updated' => $this->onUpdated($data),
            'customer.subscription.deleted' => $this->onDeleted($data),
            default => null,
        };
    }

    private function onUpdated(array $subscription): void
    {
        $structure = $this->structureForCustomer($subscription['customer'] ?? null);
        if ($structure === null) {
            return;
        }

        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
        $tier = $priceId !== null ? $this->tierForPrice($priceId) : null;

        if ($tier === null) {
            return;
        }

        Structure::query()
            ->where('id', $structure->id)
            ->update(['tier' => $tier->value]);
    }

    private function onDeleted(array $subscription): void
    {
        $structure = $this->structureForCustomer($subscription['customer'] ?? null);
        if ($structure === null) {
            return;
        }

        Structure::query()
            ->where('id', $structure->id)
            ->update(['tier' => StructureTier::Essential->value]);
    }

    private function structureForCustomer(?string $stripeCustomerId): ?Structure
    {
        if ($stripeCustomerId === null || $stripeCustomerId === '') {
            return null;
        }

        return Structure::query()
            ->where('stripe_id', $stripeCustomerId)
            ->first();
    }

    private function tierForPrice(string $priceId): ?StructureTier
    {
        foreach (StructureTier::cases() as $tier) {
            if (config("billing.prices.{$tier->value}") === $priceId) {
                return $tier;
            }
        }

        return null;
    }
}
