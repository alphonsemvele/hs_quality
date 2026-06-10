<?php

declare(strict_types=1);

use App\Enums\QvctMood;
use App\Models\QvctJournalEntry;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('lets a user record a private journal entry via the web', function (): void {
    $user = actingAsRole('intervenant');

    $this->post('/qvct/journal', [
        'body' => 'Journée chargée mais constructive.',
        'mood' => QvctMood::Positif->value,
    ])->assertRedirect();

    $entry = QvctJournalEntry::query()->where('user_id', $user->id)->firstOrFail();
    expect($entry->mood)->toBe(QvctMood::Positif)
        ->and($entry->mood_score)->toBe(4)
        ->and($entry->shared_with_rh)->toBeFalse()
        ->and($entry->body)->toBe('Journée chargée mais constructive.');
});

it('lets a user share an entry with RH explicitly', function (): void {
    $user = actingAsRole('intervenant');

    $this->post('/qvct/journal', [
        'body' => 'Tensions avec un collègue.',
        'mood' => QvctMood::Negatif->value,
        'shared_with_rh' => '1',
    ])->assertRedirect();

    expect(QvctJournalEntry::query()->where('user_id', $user->id)->first()->shared_with_rh)->toBeTrue();
});

it('rejects an invalid mood value (422)', function (): void {
    actingAsRole('intervenant');

    $this->from('/qvct/journal')
        ->post('/qvct/journal', [
            'body' => 'OK',
            'mood' => 'not_a_mood',
        ])
        ->assertSessionHasErrors('mood');
});

it('renders the journal page with the user own entries only', function (): void {
    $user = actingAsRole('intervenant');
    QvctJournalEntry::factory()->create([
        'structure_id' => $user->structure_id,
        'user_id' => $user->id,
        'body' => 'Entrée à moi',
    ]);
    // Another user's entry — must NOT appear on this user's journal.
    $other = User::factory()->forStructure($user->structure)->create();
    QvctJournalEntry::factory()->create([
        'structure_id' => $user->structure_id,
        'user_id' => $other->id,
        'body' => 'Entrée de quelqu\'un d\'autre',
    ]);

    $this->get('/qvct/journal')
        ->assertOk()
        ->assertInertia(fn ($p) => $p
            ->component('dashboard/qvct/journal/index')
            ->has('entries', 1)
            ->where('entries.0.content', 'Entrée à moi'));
});
