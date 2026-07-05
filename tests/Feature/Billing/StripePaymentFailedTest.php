<?php

declare(strict_types=1);

use App\Listeners\Billing\SyncSubscriptionToStructure;
use App\Models\Structure;
use App\Models\User;
use App\Notifications\Billing\SubscriptionPaymentFailedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;
use Laravel\Cashier\Events\WebhookReceived;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Notification::fake();

    $this->structure = Structure::factory()->create(['stripe_id' => 'cus_test_123']);
    $this->dirigeant = User::factory()->forStructure($this->structure)->create([
        'type' => 'dirigeant',
    ]);
});

it('notifies dirigeants when a Stripe invoice.payment_failed webhook arrives', function (): void {
    $event = new WebhookReceived([
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'customer' => 'cus_test_123',
                'amount_due' => 12000,
                'currency' => 'eur',
                'hosted_invoice_url' => 'https://invoice.stripe.com/test',
            ],
        ],
    ]);

    app(SyncSubscriptionToStructure::class)->handle($event);

    Notification::assertSentTo($this->dirigeant, SubscriptionPaymentFailedNotification::class);
});

it('ignores payment_failed webhooks for an unknown Stripe customer', function (): void {
    $event = new WebhookReceived([
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'customer' => 'cus_unknown',
                'amount_due' => 5000,
                'currency' => 'eur',
            ],
        ],
    ]);

    app(SyncSubscriptionToStructure::class)->handle($event);

    Notification::assertNothingSent();
});

it('does not notify intervenants or coordinateurs on payment failure — dirigeants only', function (): void {
    $coord = User::factory()->forStructure($this->structure)->create(['type' => 'coordinateur']);
    $intervenant = User::factory()->forStructure($this->structure)->create(['type' => 'intervenant']);

    $event = new WebhookReceived([
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'customer' => 'cus_test_123',
                'amount_due' => 12000,
                'currency' => 'eur',
            ],
        ],
    ]);

    app(SyncSubscriptionToStructure::class)->handle($event);

    Notification::assertSentTo($this->dirigeant, SubscriptionPaymentFailedNotification::class);
    Notification::assertNotSentTo($coord, SubscriptionPaymentFailedNotification::class);
    Notification::assertNotSentTo($intervenant, SubscriptionPaymentFailedNotification::class);
});
