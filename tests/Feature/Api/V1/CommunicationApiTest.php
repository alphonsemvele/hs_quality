<?php

declare(strict_types=1);

use App\Events\MessagePosted;
use App\Events\NewsPostPublished;
use App\Models\DiscussionGroup;
use App\Models\DiscussionGroupMember;
use App\Models\NewsFeedPost;
use App\Models\QaAnswer;
use App\Models\QaQuestion;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Event::fake();
});

// ── Messages ─────────────────────────────────────────────────────────────

it('posts a message to a group and broadcasts MessagePosted', function (): void {
    $coord = actingAsApiRole('coordinateur');
    $group = DiscussionGroup::factory()->forStructure($coord->structure)->create();
    DiscussionGroupMember::create([
        'structure_id' => $group->structure_id,
        'discussion_group_id' => $group->id,
        'user_id' => $coord->id,
        'role' => 'admin',
    ]);

    $this->postJson("/api/v1/communication/groups/{$group->id}/messages", [
        'body' => 'Bonjour équipe',
    ])->assertCreated();

    Event::assertDispatched(MessagePosted::class);
});

it('rejects message post by non-member', function (): void {
    $coord = actingAsApiRole('coordinateur');
    $group = DiscussionGroup::factory()->forStructure($coord->structure)->create();
    // coord is NOT a member, and lacks moderate? actually coordinateur HAS messages.moderate
    // so test with intervenant instead.

    $intervenant = actingAsApiRole('intervenant', $coord->structure);
    $groupForIntervenant = DiscussionGroup::factory()->forStructure($coord->structure)->create();

    $this->postJson("/api/v1/communication/groups/{$groupForIntervenant->id}/messages", [
        'body' => 'Tentative',
    ])->assertForbidden();
});

it('validates body presence for message send', function (): void {
    $coord = actingAsApiRole('coordinateur');
    $group = DiscussionGroup::factory()->forStructure($coord->structure)->create();
    DiscussionGroupMember::create([
        'structure_id' => $group->structure_id,
        'discussion_group_id' => $group->id,
        'user_id' => $coord->id,
        'role' => 'member',
    ]);

    $this->postJson("/api/v1/communication/groups/{$group->id}/messages", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['body']);
});

// ── News feed ────────────────────────────────────────────────────────────

it('publishes a news post and broadcasts NewsPostPublished', function (): void {
    $coord = actingAsApiRole('coordinateur');

    $this->postJson('/api/v1/communication/news', [
        'title' => 'Nouvelle procédure',
        'body' => 'Détails…',
    ])->assertCreated();

    Event::assertDispatched(NewsPostPublished::class);
});

it('rejects news publication by intervenant (lacks newsfeed.post)', function (): void {
    actingAsApiRole('intervenant');

    $this->postJson('/api/v1/communication/news', [
        'title' => 'Tentative',
        'body' => 'Corps',
    ])->assertForbidden();
});

it('lists news posts excluding archived', function (): void {
    $coord = actingAsApiRole('coordinateur');
    NewsFeedPost::factory()->forStructure($coord->structure)->count(2)->create();
    NewsFeedPost::factory()->forStructure($coord->structure)->create(['archived_at' => now()]);

    $this->getJson('/api/v1/communication/news')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

// ── Q&A ──────────────────────────────────────────────────────────────────

it('asks a question via the API', function (): void {
    $intervenant = actingAsApiRole('intervenant');

    $this->postJson('/api/v1/communication/qa/questions', [
        'title' => 'Comment gérer une chute ?',
        'body' => 'Détails…',
    ])->assertCreated()->assertJsonPath('title', 'Comment gérer une chute ?');
});

it('answers a question and votes idempotently', function (): void {
    $coord = actingAsApiRole('coordinateur');
    $q = QaQuestion::factory()->forStructure($coord->structure)->create(['author_id' => $coord->id]);

    // Answer
    $answerResponse = $this->postJson("/api/v1/communication/qa/questions/{$q->id}/answers", [
        'body' => 'Suivre §3.2 du protocole.',
    ])->assertCreated();
    $answerId = $answerResponse->json('id');

    // Vote (toggle on)
    $this->postJson("/api/v1/communication/qa/answers/{$answerId}/vote")
        ->assertOk()
        ->assertJsonPath('upvotes', 1);

    // Vote again (toggle off)
    $this->postJson("/api/v1/communication/qa/answers/{$answerId}/vote")
        ->assertOk()
        ->assertJsonPath('upvotes', 0);
});

it('only the question author may accept an answer via the API', function (): void {
    $author = actingAsApiRole('coordinateur');
    $q = QaQuestion::factory()->forStructure($author->structure)->create(['author_id' => $author->id]);
    $answer = QaAnswer::factory()->forQuestion($q)->create();

    // Author accepts → 200
    $this->patchJson("/api/v1/communication/qa/questions/{$q->id}/accept-answer", [
        'answer_id' => $answer->id,
    ])->assertOk();

    // Switch user — different intervenant tries to accept
    $intruder = actingAsApiRole('intervenant', $author->structure);

    $this->patchJson("/api/v1/communication/qa/questions/{$q->id}/accept-answer", [
        'answer_id' => $answer->id,
    ])->assertForbidden();
});

it('requires authentication to access communication endpoints', function (): void {
    $this->getJson('/api/v1/communication/news')->assertUnauthorized();
});
