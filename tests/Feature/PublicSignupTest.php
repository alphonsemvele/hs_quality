<?php

declare(strict_types=1);

use App\Enums\UserType;
use App\Models\Structure;
use App\Models\User;
use App\Notifications\StructureWelcomeNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function validSignupPayload(array $overrides = []): array
{
    return array_merge([
        'structure_name' => 'SAAD Soleil',
        'structure_type' => 'SAAD',
        'siret' => '12345678901234',
        'address' => '5 rue Test, 75001 Paris',
        'first_name' => 'Alice',
        'last_name' => 'Durand',
        'email' => 'alice@soleil-saad.fr',
        'phone' => '0612345678',
        'accept_cgu' => true,
        'website' => '',
    ], $overrides);
}

it('renders the public signup page', function (): void {
    $this->get('/inscription')->assertSuccessful();
});

it('provisions a structure and its dirigeant on a valid submission', function (): void {
    Notification::fake();

    $response = $this->post('/inscription', validSignupPayload());

    $response->assertRedirect('/inscription/confirmation');

    $structure = Structure::query()->where('name', 'SAAD Soleil')->first();
    expect($structure)->not->toBeNull();
    expect($structure->siret)->toBe('12345678901234');

    $dirigeant = User::query()->where('email', 'alice@soleil-saad.fr')->first();
    expect($dirigeant)->not->toBeNull();
    expect($dirigeant->structure_id)->toBe($structure->id);
    expect($dirigeant->type)->toBe(UserType::Dirigeant);
    expect($dirigeant->hasRole('dirigeant'))->toBeTrue();

    Notification::assertSentTo($dirigeant, StructureWelcomeNotification::class);
});

it('rejects a payload without CGU acceptance', function (): void {
    $this->post('/inscription', validSignupPayload(['accept_cgu' => false]))
        ->assertSessionHasErrors('accept_cgu');
});

it('rejects a malformed SIRET', function (): void {
    $this->post('/inscription', validSignupPayload(['siret' => '1234']))
        ->assertSessionHasErrors('siret');
});

it('rejects an unknown structure type', function (): void {
    $this->post('/inscription', validSignupPayload(['structure_type' => 'EHPAD']))
        ->assertSessionHasErrors('structure_type');
});

it('rejects a duplicate email', function (): void {
    User::factory()->create(['email' => 'duplicate@example.com']);

    $this->post('/inscription', validSignupPayload(['email' => 'duplicate@example.com']))
        ->assertSessionHasErrors('email');
});

it('blocks signups with a filled honeypot field', function (): void {
    $response = $this->post('/inscription', validSignupPayload(['website' => 'spam']));

    $response->assertSessionHasErrors('website');
    expect(Structure::query()->where('name', 'SAAD Soleil')->exists())->toBeFalse();
});

it('renders the confirmation page with the email passed via flash', function (): void {
    $response = $this->withSession(['signup_email' => 'alice@soleil-saad.fr'])
        ->get('/inscription/confirmation');

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];
    expect($props['email'])->toBe('alice@soleil-saad.fr');
});
