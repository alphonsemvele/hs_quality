<?php

declare(strict_types=1);

/**
 * C7 — Full subscription lifecycle test (no Stripe; pure DB state machine).
 *
 * Inserts Cashier subscription rows directly into the `subscriptions` table
 * to simulate each lifecycle stage and asserts the behaviour observable
 * through the Structure / Subscription models. These run without STRIPE_SECRET.
 */

use App\Enums\StructureTier;
use App\Models\Structure;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function fakeSubscription(Structure $structure, array $attrs = []): Subscription
{
    return $structure->subscriptions()->create(array_merge([
        'type' => BillingService::SUBSCRIPTION_TYPE,
        'stripe_id' => 'sub_fake_'.uniqid(),
        'stripe_status' => 'active',
        'stripe_price' => 'price_pro_test',
        'quantity' => 1,
        'trial_ends_at' => null,
        'ends_at' => null,
    ], $attrs));
}

it('trial stage — stripe_status=trialing, onTrial() true, structure on Pro', function (): void {
    $structure = Structure::factory()->create(['tier' => StructureTier::Pro]);

    fakeSubscription($structure, [
        'stripe_status' => 'trialing',
        'trial_ends_at' => now()->addDays(14),
    ]);

    $sub = $structure->subscription(BillingService::SUBSCRIPTION_TYPE);

    expect($sub)->not->toBeNull();
    expect($sub->onTrial())->toBeTrue();
    expect($sub->active())->toBeTrue();
    expect($structure->tier)->toBe(StructureTier::Pro);
});

it('paid stage — stripe_status=active, no trial, onTrial() false', function (): void {
    $structure = Structure::factory()->create(['tier' => StructureTier::Pro]);

    fakeSubscription($structure, [
        'stripe_status' => 'active',
        'trial_ends_at' => now()->subDay(),
    ]);

    $sub = $structure->subscription(BillingService::SUBSCRIPTION_TYPE);

    expect($sub->onTrial())->toBeFalse();
    expect($sub->active())->toBeTrue();
});

it('cancel stage — ends_at in the future, onGracePeriod() true', function (): void {
    $structure = Structure::factory()->create(['tier' => StructureTier::Pro]);

    fakeSubscription($structure, [
        'stripe_status' => 'active',
        'ends_at' => now()->addDays(20),
    ]);

    $sub = $structure->subscription(BillingService::SUBSCRIPTION_TYPE);

    expect($sub->canceled())->toBeTrue();
    expect($sub->onGracePeriod())->toBeTrue();
    expect($sub->active())->toBeTrue();
});

it('grace expired — ends_at in the past, subscription no longer active', function (): void {
    $structure = Structure::factory()->create(['tier' => StructureTier::Essential]);

    fakeSubscription($structure, [
        'stripe_status' => 'canceled',
        'ends_at' => now()->subDay(),
    ]);

    $sub = $structure->subscription(BillingService::SUBSCRIPTION_TYPE);

    expect($sub->canceled())->toBeTrue();
    expect($sub->onGracePeriod())->toBeFalse();
    expect($sub->active())->toBeFalse();
    expect($structure->tier)->toBe(StructureTier::Essential);
});

it('BillingService::cancel() throws 422 on already-cancelled subscription', function (): void {
    $structure = Structure::factory()->create(['tier' => StructureTier::Pro]);

    fakeSubscription($structure, [
        'stripe_status' => 'active',
        'ends_at' => now()->addDays(10),
    ]);

    expect(fn () => app(BillingService::class)->cancel($structure))
        ->toThrow(HttpException::class, 'No active subscription');
});
