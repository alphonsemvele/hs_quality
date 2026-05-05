<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Billing configuration — Phase 2 / C1
|--------------------------------------------------------------------------
| Cashier-Stripe lives in config/cashier.php (published). This file holds
| project-specific billing knobs (trial length, default tier, Stripe price
| IDs per tier) so they don't leak into config/cashier.php (which is
| upstream-owned and gets rewritten on package update).
|
| ENV vars:
|   BILLING_TRIAL_DAYS         — trial length in days, default 30
|   BILLING_DEFAULT_TIER       — tier assigned on signup, default 'essential'
|   BILLING_PRICE_ESSENTIAL    — Stripe price_xxx for the Essential plan
|   BILLING_PRICE_PRO          — Stripe price_xxx for the Pro plan
|   BILLING_PRICE_PREMIUM      — Stripe price_xxx for the Premium plan
*/

return [
    /*
    | Free trial length in days. The structure starts on the default tier
    | with no payment method until the trial ends. After expiry, access
    | falls back to whatever the unpaid policy is (read-only access, etc.).
    | Configurable via BILLING_TRIAL_DAYS for staging / pilot tweaks.
    */
    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 30),

    /*
    | Tier assigned to a new structure on signup. Most signups land on
    | Essential and upgrade later; pilot tenants get manually placed on
    | Pro by an admin until they convert.
    */
    'default_tier' => env('BILLING_DEFAULT_TIER', 'essential'),

    /*
    | Stripe price IDs per tier. Set per environment via env vars so test
    | (sk_test) and production (sk_live) Stripe accounts each map to their
    | own price IDs. Leaving these null is OK in test mode where the
    | price is created on the fly via Stripe::prices()->create().
    */
    'prices' => [
        'essential' => env('BILLING_PRICE_ESSENTIAL'),
        'pro' => env('BILLING_PRICE_PRO'),
        'premium' => env('BILLING_PRICE_PREMIUM'),
    ],
];
