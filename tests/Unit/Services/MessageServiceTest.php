<?php

declare(strict_types=1);

use App\Events\MessagePosted;
use App\Models\DiscussionGroup;
use App\Models\Message;
use App\Models\MessageReadCursor;
use App\Models\Structure;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(MessageService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->author = User::factory()->forStructure($this->structure)->create();
    $this->group = DiscussionGroup::factory()->forStructure($this->structure)->create();
});

// ── send ──────────────────────────────────────────────────────────────────

it('creates a message and dispatches MessagePosted', function (): void {
    Event::fake();

    $message = $this->service->send($this->group, $this->author, 'Bonjour tout le monde');

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->body)->toBe('Bonjour tout le monde')
        ->and($message->author_id)->toBe($this->author->id)
        ->and($message->discussion_group_id)->toBe($this->group->id)
        ->and($message->structure_id)->toBe($this->structure->id);

    Event::assertDispatched(MessagePosted::class, function (MessagePosted $event) use ($message): bool {
        return $event->message->is($message);
    });
});

it('stores optional attachments json', function (): void {
    Event::fake();

    $attachments = [['name' => 'photo.jpg', 'path' => 's3://bucket/photo.jpg']];
    $message = $this->service->send($this->group, $this->author, 'Voir pièce jointe', $attachments);

    expect($message->attachments)->toBe($attachments);
});

// ── edit ──────────────────────────────────────────────────────────────────

it('edits a message within the window and stamps edited_at', function (): void {
    $message = Message::factory()->inGroup($this->group, $this->author)->create();

    $updated = $this->service->edit($message, $this->author, 'Texte corrigé');

    expect($updated->body)->toBe('Texte corrigé')
        ->and($updated->edited_at)->not->toBeNull();
});

it('rejects edit by a non-author', function (): void {
    $other = User::factory()->forStructure($this->structure)->create();
    $message = Message::factory()->inGroup($this->group, $this->author)->create();

    expect(fn () => $this->service->edit($message, $other, 'Tentative'))
        ->toThrow(HttpException::class);
});

it('rejects edit after the 5-minute window', function (): void {
    $message = Message::factory()->inGroup($this->group, $this->author)->create([
        'created_at' => now()->subSeconds(MessageService::EDIT_WINDOW_SECONDS + 1),
    ]);

    expect(fn () => $this->service->edit($message, $this->author, 'Trop tard'))
        ->toThrow(HttpException::class);
});

it('accepts edit exactly at the window boundary (last second)', function (): void {
    $message = Message::factory()->inGroup($this->group, $this->author)->create([
        'created_at' => now()->subSeconds(MessageService::EDIT_WINDOW_SECONDS - 1),
    ]);

    $updated = $this->service->edit($message, $this->author, 'Juste à temps');

    expect($updated->body)->toBe('Juste à temps');
});

// ── delete ────────────────────────────────────────────────────────────────

it('soft-deletes the message', function (): void {
    $message = Message::factory()->inGroup($this->group, $this->author)->create();

    $this->service->delete($message);

    expect(Message::withTrashed()->find($message->id)?->deleted_at)->not->toBeNull();
    expect(Message::find($message->id))->toBeNull();
});

// ── markRead + unreadCount ────────────────────────────────────────────────

it('markRead creates a cursor for the reader', function (): void {
    $reader = User::factory()->forStructure($this->structure)->create();
    $message = Message::factory()->inGroup($this->group, $this->author)->create();

    $this->service->markRead($message, $reader);

    $cursor = MessageReadCursor::withoutGlobalScopes()
        ->where('user_id', $reader->id)
        ->where('discussion_group_id', $this->group->id)
        ->first();

    expect($cursor)->not->toBeNull();
    expect($cursor->last_read_message_id)->toBe($message->id);
});

it('markRead is idempotent — calling twice does not create duplicate cursors', function (): void {
    $reader = User::factory()->forStructure($this->structure)->create();
    $message = Message::factory()->inGroup($this->group, $this->author)->create();

    $this->service->markRead($message, $reader);
    $this->service->markRead($message, $reader);

    $count = MessageReadCursor::withoutGlobalScopes()
        ->where('user_id', $reader->id)
        ->where('discussion_group_id', $this->group->id)
        ->count();

    expect($count)->toBe(1);
});

it('unreadCount returns 0 when user has no cursor', function (): void {
    $reader = User::factory()->forStructure($this->structure)->create();
    Message::factory()->inGroup($this->group, $this->author)->create();

    expect($this->service->unreadCount($this->group, $reader))->toBe(0);
});

it('unreadCount returns messages after the cursor', function (): void {
    $reader = User::factory()->forStructure($this->structure)->create();

    $old = Message::factory()->inGroup($this->group, $this->author)->create([
        'created_at' => now()->subMinutes(10),
    ]);
    Message::factory()->inGroup($this->group, $this->author)->create([
        'created_at' => now()->subMinutes(2),
    ]);
    Message::factory()->inGroup($this->group, $this->author)->create([
        'created_at' => now()->subMinute(),
    ]);

    // Mark the first message as read — 2 newer ones are unread.
    $this->service->markRead($old, $reader);

    expect($this->service->unreadCount($this->group, $reader))->toBe(2);
});
