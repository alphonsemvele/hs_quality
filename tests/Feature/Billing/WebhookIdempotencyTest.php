<?php

declare(strict_types=1);

use App\Enums\StructureTier;
use App\Listeners\Billing\SyncSubscriptionToStructure;
use App\Models\Structure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Events\WebhookReceived;

uses(RefreshDatabase::class);

/**
 * C2 — Billing webhook idempotency.
 *
 * Dispatches WebhookReceived directly to the listener to test business
 * logic without requiring a real Stripe signature or network call.
 * Replaying the same event twice must produce the same DB state.
 */
function webhookPayload(string $type, string $customerId, ?string $priceId = null): array
{
    $payload = [
        'type' => $type,
        'data' => [
            'object' => [
                'customer' => $customerId,
                'items' => [
                    'data' => [
                        ['price' => ['id' => $priceId ?? 'price_unknown']],
                    ],
                ],
            ],
        ],
    ];

    return $payload;
}

it('upgrades structure tier when subscription.updated arrives', function (): void {
    config(['billing.prices.pro' => 'price_pro_test']);
    $structure = Structure::factory()->create(['stripe_id' => 'cus_test_001', 'tier' => StructureTier::Essential]);

    $listener = app(SyncSubscriptionToStructure::class);
    $listener->handle(new WebhookReceived(
        webhookPayload('customer.subscription.updated', 'cus_test_001', 'price_pro_test')
    ));

    expect($structure->fresh()->tier)->toBe(StructureTier::Pro);
});

it('is idempotent — replaying subscription.updated twice does not corrupt tier', function (): void {
    config(['billing.prices.pro' => 'price_pro_test']);
    $structure = Structure::factory()->create(['stripe_id' => 'cus_test_002', 'tier' => StructureTier::Essential]);

    $listener = app(SyncSubscriptionToStructure::class);
    $payload = new WebhookReceived(
        webhookPayload('customer.subscription.updated', 'cus_test_002', 'price_pro_test')
    );

    $listener->handle($payload);
    $listener->handle($payload);

    expect($structure->fresh()->tier)->toBe(StructureTier::Pro);
});

it('resets tier to essential when subscription.deleted arrives', function (): void {
    $structure = Structure::factory()->create(['stripe_id' => 'cus_test_003', 'tier' => StructureTier::Pro]);

    $listener = app(SyncSubscriptionToStructure::class);
    $listener->handle(new WebhookReceived(
        webhookPayload('customer.subscription.deleted', 'cus_test_003')
    ));

    expect($structure->fresh()->tier)->toBe(StructureTier::Essential);
});

it('is idempotent — replaying subscription.deleted twice stays on essential', function (): void {
    $structure = Structure::factory()->create(['stripe_id' => 'cus_test_004', 'tier' => StructureTier::Pro]);

    $listener = app(SyncSubscriptionToStructure::class);
    $payload = new WebhookReceived(
        webhookPayload('customer.subscription.deleted', 'cus_test_004')
    );

    $listener->handle($payload);
    $listener->handle($payload);

    expect($structure->fresh()->tier)->toBe(StructureTier::Essential);
});

it('silently ignores an unknown stripe customer ID', function (): void {
    $listener = app(SyncSubscriptionToStructure::class);

    // Must not throw — unknown customers are skipped gracefully
    $listener->handle(new WebhookReceived(
        webhookPayload('customer.subscription.updated', 'cus_does_not_exist', 'price_pro_test')
    ));

    expect(true)->toBeTrue();
});

it('silently ignores an unrecognised event type', function (): void {
    $structure = Structure::factory()->create(['stripe_id' => 'cus_test_005', 'tier' => StructureTier::Pro]);

    $listener = app(SyncSubscriptionToStructure::class);
    $listener->handle(new WebhookReceived(
        webhookPayload('invoice.payment_succeeded', 'cus_test_005')
    ));

    expect($structure->fresh()->tier)->toBe(StructureTier::Pro);
});

it('silently ignores subscription.updated when the price maps to no known tier', function (): void {
    config(['billing.prices.pro' => 'price_pro_test']);
    $structure = Structure::factory()->create(['stripe_id' => 'cus_test_006', 'tier' => StructureTier::Essential]);

    $listener = app(SyncSubscriptionToStructure::class);
    $listener->handle(new WebhookReceived(
        webhookPayload('customer.subscription.updated', 'cus_test_006', 'price_unknown_xxx')
    ));

    expect($structure->fresh()->tier)->toBe(StructureTier::Essential);
});
