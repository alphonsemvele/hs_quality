<?php

declare(strict_types=1);

use App\Models\QvctJournalEntry;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('intervenant can write a journal entry via API', function (): void {
    $intervenant = actingAsApiRole('intervenant');

    $response = $this->postJson('/api/v1/qvct/journal', [
        'body' => 'Tour assez calme aujourd\'hui.',
        'mood' => 'positif',
        'shared_with_rh' => false,
    ]);

    $response->assertCreated();
    expect(QvctJournalEntry::where('user_id', $intervenant->id)->count())->toBe(1);
});

it('intervenant sees only their own entries on /journal/mine', function (): void {
    $intervenant = actingAsApiRole('intervenant');

    QvctJournalEntry::factory()->forUser($intervenant)->count(2)->create();

    $other = User::factory()->forStructure($intervenant->structure)->intervenant()->create();
    QvctJournalEntry::factory()->forUser($other)->count(3)->create();

    $response = $this->getJson('/api/v1/qvct/journal/mine');
    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

it('rh sees only shared entries on /journal/shared-with-rh', function (): void {
    $rh = actingAsApiRole('rh');
    $intervenant = User::factory()->forStructure($rh->structure)->intervenant()->create();

    QvctJournalEntry::factory()->forUser($intervenant)->count(2)->create(); // unshared
    QvctJournalEntry::factory()->forUser($intervenant)->sharedWithRh()->count(3)->create();

    $response = $this->getJson('/api/v1/qvct/journal/shared-with-rh');
    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(3);
});

it('intervenant cannot list shared-with-rh queue', function (): void {
    actingAsApiRole('intervenant');

    $this->getJson('/api/v1/qvct/journal/shared-with-rh')->assertForbidden();
});

it('coordinateur cannot list shared-with-rh queue (RH-only gate)', function (): void {
    actingAsApiRole('coordinateur');

    $this->getJson('/api/v1/qvct/journal/shared-with-rh')->assertForbidden();
});

it('rejects journal write with empty body at validation', function (): void {
    actingAsApiRole('intervenant');

    $this->postJson('/api/v1/qvct/journal', [
        'body' => '',
        'mood' => 'neutre',
    ])->assertStatus(422);
});

it('rejects journal write with invalid mood at validation', function (): void {
    actingAsApiRole('intervenant');

    $this->postJson('/api/v1/qvct/journal', [
        'body' => 'Test',
        'mood' => 'euphoric',
    ])->assertStatus(422);
});

// ── Sync op coverage ──────────────────────────────────────────────────────────

it('writes a journal entry via the sync batch envelope', function (): void {
    $intervenant = actingAsApiRole('intervenant');

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'qvct.write_journal',
            'payload' => [
                'body' => 'Note offline depuis le terrain.',
                'mood' => 'neutre',
                'shared_with_rh' => false,
            ],
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('success');
    expect($response->json('results.0.server_state.mood'))->toBe('neutre');
    expect(QvctJournalEntry::where('user_id', $intervenant->id)->count())->toBe(1);
});

it('rejects journal sync op with missing body or mood', function (): void {
    actingAsApiRole('intervenant');

    $response = $this->postJson('/api/v1/sync/batch', [
        'operations' => [[
            'client_op_id' => (string) Str::uuid(),
            'kind' => 'qvct.write_journal',
            'payload' => ['mood' => 'positif'], // body missing
        ]],
    ]);

    $response->assertStatus(207);
    expect($response->json('results.0.status'))->toBe('error');
});
