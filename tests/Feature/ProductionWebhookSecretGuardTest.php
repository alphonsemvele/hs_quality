<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;

/**
 * Refuse to boot in production when STRIPE_WEBHOOK_SECRET is empty.
 *
 * Without the secret Cashier's VerifyWebhookSignature middleware is
 * NOT applied, so /stripe/webhook accepts unsigned payloads — an
 * obvious way to forge a subscription.deleted event and downgrade a
 * paying tenant. The guard catches this at boot.
 */
function callWebhookSecretGuard(string $env, ?string $secret): void
{
    $appEnv = config('app.env');
    $cashierSecret = config('cashier.webhook.secret');

    config(['app.env' => $env, 'cashier.webhook.secret' => $secret]);
    app()['env'] = $env;

    try {
        $provider = new AppServiceProvider(app());
        $method = (new ReflectionClass($provider))->getMethod('guardProductionWebhookSecret');
        $method->setAccessible(true);
        $method->invoke($provider);
    } finally {
        config(['app.env' => $appEnv, 'cashier.webhook.secret' => $cashierSecret]);
        app()['env'] = $appEnv;
    }
}

it('refuses to boot when env=production and the webhook secret is missing', function (): void {
    expect(fn () => callWebhookSecretGuard('production', null))
        ->toThrow(RuntimeException::class, 'STRIPE_WEBHOOK_SECRET');
});

it('refuses to boot when env=production and the webhook secret is empty', function (): void {
    expect(fn () => callWebhookSecretGuard('production', ''))
        ->toThrow(RuntimeException::class, 'STRIPE_WEBHOOK_SECRET');
});

it('boots when env=production and the webhook secret is set', function (): void {
    expect(fn () => callWebhookSecretGuard('production', 'whsec_test_xxx'))
        ->not->toThrow(RuntimeException::class);
});

it('boots when env=local and the webhook secret is missing (developer convenience)', function (): void {
    expect(fn () => callWebhookSecretGuard('local', null))
        ->not->toThrow(RuntimeException::class);
});

it('boots when env=staging and the webhook secret is missing (test mode)', function (): void {
    expect(fn () => callWebhookSecretGuard('staging', null))
        ->not->toThrow(RuntimeException::class);
});
