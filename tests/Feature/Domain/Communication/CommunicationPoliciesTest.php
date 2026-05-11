<?php

declare(strict_types=1);

use App\Models\DiscussionGroup;
use App\Models\DiscussionGroupMember;
use App\Models\Document;
use App\Models\Message;
use App\Models\NewsFeedPost;
use App\Models\QaAnswer;
use App\Models\QaQuestion;
use App\Models\Structure;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

// ── MessagePolicy ────────────────────────────────────────────────────────

it('lets the author update their own message', function (): void {
    $author = actingAsRole('intervenant');
    $message = Message::factory()->create([
        'structure_id' => $author->structure_id,
        'author_id' => $author->id,
    ]);

    expect($author->can('update', $message))->toBeTrue();
});

it('rejects update by a non-author intervenant', function (): void {
    $author = actingAsRole('intervenant');
    $message = Message::factory()->create([
        'structure_id' => $author->structure_id,
        'author_id' => $author->id,
    ]);
    $other = actingAsRole('intervenant', $author->structure);

    expect($other->can('update', $message))->toBeFalse();
});

it('lets a coordinateur (messages.moderate) delete any message', function (): void {
    $author = actingAsRole('intervenant');
    $message = Message::factory()->create([
        'structure_id' => $author->structure_id,
        'author_id' => $author->id,
    ]);
    $mod = actingAsRole('coordinateur', $author->structure);

    expect($mod->can('delete', $message))->toBeTrue();
});

it('denies cross-tenant updates even for the author', function (): void {
    $author = actingAsRole('intervenant');
    $messageInOtherStructure = Message::factory()->create([
        // Same author, but message belongs to a different structure
        'author_id' => $author->id,
    ]);

    expect($author->can('update', $messageInOtherStructure))->toBeFalse();
});

// ── DiscussionGroupPolicy ────────────────────────────────────────────────

it('lets a group member view the group', function (): void {
    $coord = actingAsRole('coordinateur');
    $group = DiscussionGroup::factory()->forStructure($coord->structure)->create();
    DiscussionGroupMember::create([
        'structure_id' => $group->structure_id,
        'discussion_group_id' => $group->id,
        'user_id' => $coord->id,
        'role' => 'member',
    ]);

    expect($coord->can('view', $group))->toBeTrue();
});

it('denies view for non-member intervenant', function (): void {
    $coord = actingAsRole('coordinateur');
    $group = DiscussionGroup::factory()->forStructure($coord->structure)->create();
    $stranger = actingAsRole('intervenant', $coord->structure);

    expect($stranger->can('view', $group))->toBeFalse();
});

it('only messages.moderate can create a discussion group', function (): void {
    $intervenant = actingAsRole('intervenant');
    $coord = actingAsRole('coordinateur', $intervenant->structure);

    expect($intervenant->can('create', DiscussionGroup::class))->toBeFalse();
    expect($coord->can('create', DiscussionGroup::class))->toBeTrue();
});

// ── NewsFeedPostPolicy ───────────────────────────────────────────────────

it('only newsfeed.post can publish', function (): void {
    $intervenant = actingAsRole('intervenant');
    $coord = actingAsRole('coordinateur', $intervenant->structure);

    expect($intervenant->can('create', NewsFeedPost::class))->toBeFalse();
    expect($coord->can('create', NewsFeedPost::class))->toBeTrue();
});

it('any user can view a news post in their structure', function (): void {
    $coord = actingAsRole('coordinateur');
    $post = NewsFeedPost::factory()->forStructure($coord->structure)->create();
    $intervenant = actingAsRole('intervenant', $coord->structure);

    expect($intervenant->can('view', $post))->toBeTrue();
});

// ── DocumentPolicy ───────────────────────────────────────────────────────

it('lets users in the ACL view a document', function (): void {
    $coord = actingAsRole('coordinateur');
    $doc = Document::factory()
        ->forStructure($coord->structure)
        ->visibleToRoles(['coordinateur'])
        ->create(['uploaded_by' => $coord->id]);

    expect($coord->can('view', $doc))->toBeTrue();
});

it('denies users not in the document ACL', function (): void {
    $coord = actingAsRole('coordinateur');
    $doc = Document::factory()
        ->forStructure($coord->structure)
        ->visibleToRoles(['dirigeant'])
        ->create(['uploaded_by' => $coord->id]);
    $intervenant = actingAsRole('intervenant', $coord->structure);

    expect($intervenant->can('view', $doc))->toBeFalse();
});

it('only documents.upload can create documents', function (): void {
    $intervenant = actingAsRole('intervenant');
    $coord = actingAsRole('coordinateur', $intervenant->structure);

    expect($intervenant->can('create', Document::class))->toBeFalse();
    expect($coord->can('create', Document::class))->toBeTrue();
});

// ── QaQuestion / QaAnswer policies ───────────────────────────────────────

it('only the question author may accept an answer', function (): void {
    $author = actingAsRole('intervenant');
    $q = QaQuestion::factory()
        ->forStructure($author->structure)
        ->create(['author_id' => $author->id]);
    $other = actingAsRole('intervenant', $author->structure);

    expect($author->can('acceptAnswer', $q))->toBeTrue();
    expect($other->can('acceptAnswer', $q))->toBeFalse();
});

it('any user with messages.send can ask, answer, and vote', function (): void {
    $intervenant = actingAsRole('intervenant');

    expect($intervenant->can('create', QaQuestion::class))->toBeTrue();
    expect($intervenant->can('create', QaAnswer::class))->toBeTrue();
});

it('cross-tenant policy guard denies for QaQuestion', function (): void {
    $author = actingAsRole('intervenant');
    $structureB = Structure::factory()->create();
    $foreignQ = QaQuestion::factory()->forStructure($structureB)->create();

    expect($author->can('view', $foreignQ))->toBeFalse();
});
