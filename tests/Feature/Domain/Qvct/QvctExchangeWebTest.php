<?php

declare(strict_types=1);

use App\Enums\QvctExchangeAddresseeRole;
use App\Models\QvctExchangeRequest;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('lets a user create an exchange request via the web', function (): void {
    $user = actingAsRole('intervenant');

    $this->post('/qvct/exchanges', [
        'addressee_role' => QvctExchangeAddresseeRole::Rh->value,
        'message' => 'Je souhaiterais échanger sur ma charge récente.',
    ])->assertRedirect();

    $row = QvctExchangeRequest::query()->where('requester_id', $user->id)->firstOrFail();
    expect($row->addressee_role)->toBe(QvctExchangeAddresseeRole::Rh)
        ->and($row->message)->toBe('Je souhaiterais échanger sur ma charge récente.');
});

it('rejects an invalid addressee_role', function (): void {
    actingAsRole('intervenant');

    $this->from('/qvct/exchanges')
        ->post('/qvct/exchanges', [
            'addressee_role' => 'not_a_role',
            'message' => 'Bonjour',
        ])
        ->assertSessionHasErrors('addressee_role');
});

it('renders my own exchanges in the outbox', function (): void {
    $user = actingAsRole('intervenant');
    QvctExchangeRequest::factory()->create([
        'structure_id' => $user->structure_id,
        'requester_id' => $user->id,
        'addressee_role' => QvctExchangeAddresseeRole::Manager,
        'message' => 'Mon message',
    ]);

    $this->get('/qvct/exchanges')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('dashboard/qvct/exchanges/index')
            ->has('outbox', 1)
            ->where('outbox.0.to', 'Coordinateur / responsable'));
});
