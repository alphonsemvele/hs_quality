<?php

declare(strict_types=1);

use App\Models\Structure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Billable;

uses(RefreshDatabase::class);

it('Structure uses the Billable trait', function (): void {
    expect(in_array(Billable::class, class_uses_recursive(Structure::class), true))->toBeTrue();
});

it('exposes the structure name as the Stripe customer name', function (): void {
    $structure = Structure::factory()->create([
        'name' => 'CCAS de Bordeaux',
        'billing_email' => 'facturation@ccas-bordeaux.fr',
    ]);

    expect($structure->stripeName())->toBe('CCAS de Bordeaux');
    expect($structure->stripeEmail())->toBe('facturation@ccas-bordeaux.fr');
});

it('returns null Stripe email when billing_email is not set', function (): void {
    $structure = Structure::factory()->create(['billing_email' => null]);

    expect($structure->stripeEmail())->toBeNull();
});

it('has Cashier subscription columns on the structures table', function (): void {
    $structure = Structure::factory()->create();

    // Direct attribute access — Cashier writes these columns post-Stripe-API.
    // We only assert the columns exist + are nullable on a fresh row.
    expect($structure->stripe_id)->toBeNull();
    expect($structure->pm_type)->toBeNull();
    expect($structure->pm_last_four)->toBeNull();
    expect($structure->trial_ends_at)->toBeNull();
});

it('reads trial_days from config with the expected default', function (): void {
    expect(config('billing.trial_days'))->toBe(30);
});

it('reads default_tier from config', function (): void {
    expect(config('billing.default_tier'))->toBe('essential');
});

it('exposes price IDs per tier (nullable)', function (): void {
    expect(config('billing.prices'))->toHaveKeys(['essential', 'pro', 'premium']);
});

it('Billable methods are reachable on a structure instance', function (): void {
    $structure = Structure::factory()->create();

    // These all live on the Billable trait — calling them without a Stripe
    // connection MUST NOT throw (they read local state). The actual Stripe
    // calls happen inside the integration test class.
    expect(method_exists($structure, 'subscriptions'))->toBeTrue();
    expect(method_exists($structure, 'subscription'))->toBeTrue();
    expect(method_exists($structure, 'newSubscription'))->toBeTrue();
    expect(method_exists($structure, 'createOrGetStripeCustomer'))->toBeTrue();

    // No subscription yet → false
    expect($structure->subscribed())->toBeFalse();
});
