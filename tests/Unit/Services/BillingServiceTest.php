<?php

declare(strict_types=1);

use App\Enums\StructureTier;
use App\Models\Structure;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(BillingService::class);
});

it('throws a 422 when the tier has no configured Stripe price', function (): void {
    config(['billing.prices.pro' => null]);

    expect(fn () => $this->service->priceFor(StructureTier::Pro))
        ->toThrow(HttpException::class, 'No Stripe price configured');
});

it('returns the configured price for a tier', function (): void {
    config(['billing.prices.pro' => 'price_test_pro_xxx']);

    expect($this->service->priceFor(StructureTier::Pro))->toBe('price_test_pro_xxx');
});

it('subscription type is the default tier-agnostic label', function (): void {
    expect(BillingService::SUBSCRIPTION_TYPE)->toBe('default');
});

it('swap throws when the structure has no active subscription', function (): void {
    config(['billing.prices.pro' => 'price_test_pro_xxx']);
    $structure = Structure::factory()->create(['tier' => StructureTier::Essential->value]);

    expect(fn () => $this->service->swap($structure, StructureTier::Pro))
        ->toThrow(HttpException::class, 'No active subscription to swap.');
});

it('builder applies the configured trial days', function (): void {
    config([
        'billing.trial_days' => 14,
        'billing.prices.pro' => 'price_test_pro_xxx',
    ]);
    $structure = Structure::factory()->create();

    $builder = $this->service->builder($structure, StructureTier::Pro);

    // Cashier's SubscriptionBuilder doesn't expose trialDays via a
    // public getter; we assert by serialising the builder's protected
    // state via reflection — the only way to test the trialDays pass
    // without hitting Stripe.
    $reflection = new ReflectionClass($builder);
    $prop = $reflection->getProperty('trialExpires');
    $prop->setAccessible(true);
    $value = $prop->getValue($builder);

    expect($value)->not->toBeNull();
    expect($value->isAfter(now()->addDays(13)))->toBeTrue();
    expect($value->isBefore(now()->addDays(15)))->toBeTrue();
});
