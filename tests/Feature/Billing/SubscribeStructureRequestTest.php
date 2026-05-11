<?php

declare(strict_types=1);

use App\Http\Requests\Billing\SubscribeStructureRequest;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

function validateBilling(array $payload): Illuminate\Validation\Validator
{
    return Validator::make($payload, (new SubscribeStructureRequest)->rules());
}

it('rejects an empty payload', function (): void {
    expect(validateBilling([])->fails())->toBeTrue();
});

it('accepts a valid tier + payment method', function (): void {
    expect(validateBilling([
        'tier' => 'pro',
        'payment_method_id' => 'pm_card_visa_test',
    ])->fails())->toBeFalse();
});

it('rejects an unknown tier', function (): void {
    expect(validateBilling([
        'tier' => 'platinum',
        'payment_method_id' => 'pm_card_visa_test',
    ])->fails())->toBeTrue();
});

it('rejects a payment method id that does not start with pm_', function (): void {
    expect(validateBilling([
        'tier' => 'pro',
        'payment_method_id' => 'tok_card_visa_test',
    ])->fails())->toBeTrue();
});

it('only authorises a user with structure.configure', function (): void {
    $intervenant = actingAsRole('intervenant');
    $this->actingAs($intervenant);
    $req = SubscribeStructureRequest::create('/', 'POST', [
        'tier' => 'pro',
        'payment_method_id' => 'pm_card_visa_test',
    ]);
    $req->setContainer($this->app)->setRedirector($this->app['redirect']);
    $req->setUserResolver(fn () => $intervenant);

    expect($req->authorize())->toBeFalse();

    $dirigeant = actingAsRole('dirigeant', $intervenant->structure);
    $req->setUserResolver(fn () => $dirigeant);

    expect($req->authorize())->toBeTrue();
});
