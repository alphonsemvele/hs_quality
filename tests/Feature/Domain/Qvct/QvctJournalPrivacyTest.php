<?php

declare(strict_types=1);

use App\Models\QvctJournalEntry;
use App\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Privacy tests for QVCT journal entries — PHASE2_PROGRESS.md M3.30.
 * The journal is the most sensitive QVCT artefact: it identifies the
 * user and contains free-text mental-health expressions. The privacy
 * tests below pin down the only legitimate access paths and assert
 * every other path returns 403/404.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
});

function makeJournalUser(string $role, Structure $structure): User
{
    $user = User::factory()->forStructure($structure)->state(['type' => $role])->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($structure->getKey());
    $user->assignRole($role);

    return $user;
}

it('intervenant can read their own journal entry', function (): void {
    $intervenant = makeJournalUser('intervenant', $this->structure);
    $entry = QvctJournalEntry::factory()->forUser($intervenant)->create();

    expect($intervenant->can('view', $entry))->toBeTrue();
});

it('intervenant cannot read another intervenant\'s entry — even unshared', function (): void {
    $a = makeJournalUser('intervenant', $this->structure);
    $b = makeJournalUser('intervenant', $this->structure);

    $entry = QvctJournalEntry::factory()->forUser($b)->create();

    expect($a->can('view', $entry))->toBeFalse();
});

it('intervenant cannot read another intervenant\'s entry — even when shared', function (): void {
    $a = makeJournalUser('intervenant', $this->structure);
    $b = makeJournalUser('intervenant', $this->structure);

    $entry = QvctJournalEntry::factory()->forUser($b)->sharedWithRh()->create();

    expect($a->can('view', $entry))->toBeFalse();
});

it('coordinateur cannot read journal entries — even shared ones', function (): void {
    $coord = makeJournalUser('coordinateur', $this->structure);
    $intervenant = makeJournalUser('intervenant', $this->structure);

    $shared = QvctJournalEntry::factory()->forUser($intervenant)->sharedWithRh()->create();

    expect($coord->can('view', $shared))->toBeFalse();
});

it('rh cannot read an UNSHARED entry', function (): void {
    $rh = makeJournalUser('rh', $this->structure);
    $intervenant = makeJournalUser('intervenant', $this->structure);

    $unshared = QvctJournalEntry::factory()->forUser($intervenant)->create();

    expect($rh->can('view', $unshared))->toBeFalse();
});

it('rh CAN read an entry the user explicitly shared', function (): void {
    $rh = makeJournalUser('rh', $this->structure);
    $intervenant = makeJournalUser('intervenant', $this->structure);

    $shared = QvctJournalEntry::factory()->forUser($intervenant)->sharedWithRh()->create();

    expect($rh->can('view', $shared))->toBeTrue();
});

it('foreign-tenant rh cannot read a shared entry from another tenant', function (): void {
    $foreignStructure = Structure::factory()->create();
    $foreignRh = makeJournalUser('rh', $foreignStructure);

    app()->instance('current_structure', $this->structure);
    $intervenant = makeJournalUser('intervenant', $this->structure);
    $shared = QvctJournalEntry::factory()->forUser($intervenant)->sharedWithRh()->create();

    expect($foreignRh->can('view', $shared))->toBeFalse();
});

it('only the author can update or delete their entry', function (): void {
    $author = makeJournalUser('intervenant', $this->structure);
    $other = makeJournalUser('intervenant', $this->structure);
    $rh = makeJournalUser('rh', $this->structure);

    $entry = QvctJournalEntry::factory()->forUser($author)->sharedWithRh()->create();

    expect($author->can('update', $entry))->toBeTrue()
        ->and($author->can('delete', $entry))->toBeTrue()
        ->and($other->can('update', $entry))->toBeFalse()
        ->and($other->can('delete', $entry))->toBeFalse()
        ->and($rh->can('update', $entry))->toBeFalse()
        ->and($rh->can('delete', $entry))->toBeFalse();
});

// ── Cross-tenant query scope ──────────────────────────────────────────────────

it('returns zero journal entries when no tenant is bound', function (): void {
    $user = User::factory()->forStructure($this->structure)->create();
    QvctJournalEntry::factory()->forUser($user)->count(3)->create();

    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }

    expect(QvctJournalEntry::count())->toBe(0);
});

it('only returns entries from the current tenant', function (): void {
    $a = Structure::factory()->create();
    $b = Structure::factory()->create();
    $userA = User::factory()->forStructure($a)->create();
    $userB = User::factory()->forStructure($b)->create();

    QvctJournalEntry::factory()->forUser($userA)->count(2)->create();
    QvctJournalEntry::factory()->forUser($userB)->count(4)->create();

    app()->instance('current_structure', $a);
    expect(QvctJournalEntry::count())->toBe(2);

    app()->instance('current_structure', $b);
    expect(QvctJournalEntry::count())->toBe(4);
});

it('encrypts the body column at rest', function (): void {
    $user = User::factory()->forStructure($this->structure)->create();
    $entry = QvctJournalEntry::factory()->forUser($user)->create([
        'body' => 'I felt overwhelmed during today\'s tour.',
    ]);

    $rawRow = DB::table('qvct_journal_entries')->where('id', $entry->id)->first();
    expect($rawRow->body)->not->toContain('overwhelmed');
    // And the model's accessor returns plaintext
    expect($entry->fresh()->body)->toBe('I felt overwhelmed during today\'s tour.');
});
