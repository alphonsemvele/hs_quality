<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Stripe testmode integration tests
|--------------------------------------------------------------------------
| These tests hit the real Stripe API in **testmode** (not live mode —
| no real money moves). They are SKIPPED automatically unless the
| `STRIPE_SECRET` env var is set to a `sk_test_...` key.
|
| Why not Http::fake()? Because the Stripe SDK signs requests, parses
| Stripe's exact JSON-error envelope, and surfaces SDK-level errors
| (e.g. "this price was created on a different account"). Faking the
| HTTP layer would require us to pretend to be Stripe in shape and
| version — fragile, and silently breaks on every Stripe SDK upgrade.
|
| To run locally:
|   STRIPE_SECRET=sk_test_xxx php artisan test --filter=StructureSubscription
|
| In CI: store the test key in the secret manager and surface it as
| STRIPE_SECRET on the test job. Each test creates and deletes its
| own Stripe customer/product/price, so the test account stays clean.
*/

use App\Enums\StructureTier;
use App\Models\Structure;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\StripeClient;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (empty($_ENV['STRIPE_SECRET']) && empty($_SERVER['STRIPE_SECRET']) && empty(getenv('STRIPE_SECRET'))) {
        test()->markTestSkipped(
            'STRIPE_SECRET not set — Stripe integration tests are opt-in. '
            .'Run with `STRIPE_SECRET=sk_test_xxx php artisan test ...`'
        );
    }

    test()->stripe = new StripeClient(config('cashier.secret'));
});

afterEach(function (): void {
    // Clean up any Stripe customer this test created so the testmode
    // account doesn't accumulate orphaned customers.
    if (! isset(test()->createdCustomerIds)) {
        return;
    }
    foreach (test()->createdCustomerIds as $cid) {
        try {
            test()->stripe->customers->delete($cid);
        } catch (Throwable) {
            // Best effort — leave it for the next sweep
        }
    }
});

it('creates a Stripe customer for a Structure', function (): void {
    $structure = Structure::factory()->create([
        'name' => 'Test Structure '.uniqid(),
        'billing_email' => 'billing+test'.uniqid().'@qualitedomicile.test',
    ]);

    $customer = $structure->createOrGetStripeCustomer();

    test()->createdCustomerIds = [$customer->id];

    expect($customer->id)->toStartWith('cus_');
    expect($structure->fresh()->stripe_id)->toBe($customer->id);
    expect($customer->name)->toBe($structure->name);
    expect($customer->email)->toBe($structure->billing_email);
});

it('a second call returns the same Stripe customer (idempotent)', function (): void {
    $structure = Structure::factory()->create([
        'name' => 'Idempotent Test '.uniqid(),
        'billing_email' => 'billing+idemp'.uniqid().'@qualitedomicile.test',
    ]);

    $first = $structure->createOrGetStripeCustomer();
    $second = $structure->createOrGetStripeCustomer();

    test()->createdCustomerIds = [$first->id];

    expect($first->id)->toBe($second->id);
});

it('subscribes a structure to Pro with a trial, mirrors the tier', function (): void {
    // Create a Stripe product + price on the fly so this test does not
    // depend on BILLING_PRICE_PRO env being set in the test account.
    // CI installations using their own price IDs can skip this single
    // test by setting BILLING_SKIP_INTEGRATION_PRICE=1.
    if (! empty(getenv('BILLING_SKIP_INTEGRATION_PRICE'))) {
        test()->markTestSkipped('BILLING_SKIP_INTEGRATION_PRICE set');
    }

    $product = test()->stripe->products->create([
        'name' => 'QualitéDomicile Pro (test)',
    ]);
    $price = test()->stripe->prices->create([
        'product' => $product->id,
        'currency' => 'eur',
        'unit_amount' => 1500, // 15 EUR/seat/month
        'recurring' => ['interval' => 'month'],
    ]);

    config([
        'billing.trial_days' => 14,
        'billing.prices.pro' => $price->id,
    ]);

    $structure = Structure::factory()->create([
        'name' => 'Subscribe Test '.uniqid(),
        'billing_email' => 'billing+sub'.uniqid().'@qualitedomicile.test',
        'tier' => 'essential',
    ]);

    $subscription = app(BillingService::class)->subscribe(
        $structure,
        StructureTier::Pro,
        'pm_card_visa', // Stripe testmode token
    );

    test()->createdCustomerIds = [$structure->fresh()->stripe_id];

    expect($subscription->stripe_status)->toBe('trialing');
    expect($subscription->stripe_price)->toBe($price->id);
    expect($subscription->trial_ends_at)->not->toBeNull();
    expect($structure->fresh()->tier->value)->toBe('pro');
});
