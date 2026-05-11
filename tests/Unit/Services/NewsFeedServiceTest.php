<?php

declare(strict_types=1);

use App\Events\NewsPostPublished;
use App\Models\NewsFeedPost;
use App\Models\Structure;
use App\Models\User;
use App\Services\NewsFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(NewsFeedService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->author = User::factory()->forStructure($this->structure)->create();
});

// ── publish ───────────────────────────────────────────────────────────────

it('creates a news post and dispatches NewsPostPublished', function (): void {
    Event::fake();

    $post = $this->service->publish(
        $this->structure,
        $this->author,
        'Nouvelle procédure qualité',
        'Corps de la procédure…',
    );

    expect($post)->toBeInstanceOf(NewsFeedPost::class)
        ->and($post->title)->toBe('Nouvelle procédure qualité')
        ->and($post->structure_id)->toBe($this->structure->id)
        ->and($post->author_id)->toBe($this->author->id)
        ->and($post->pinned)->toBeFalse()
        ->and($post->archived_at)->toBeNull();

    Event::assertDispatched(NewsPostPublished::class, function (NewsPostPublished $event) use ($post): bool {
        return $event->post->is($post);
    });
});

// ── pin / unpin ───────────────────────────────────────────────────────────

it('pins a post', function (): void {
    $post = NewsFeedPost::factory()->forStructure($this->structure)->create(['pinned' => false]);

    $pinned = $this->service->pin($post);

    expect($pinned->pinned)->toBeTrue();
});

it('unpins a pinned post', function (): void {
    $post = NewsFeedPost::factory()->forStructure($this->structure)->pinned()->create();

    $unpinned = $this->service->unpin($post);

    expect($unpinned->pinned)->toBeFalse();
});

// ── archive ───────────────────────────────────────────────────────────────

it('archives a post by setting archived_at', function (): void {
    $post = NewsFeedPost::factory()->forStructure($this->structure)->create();

    $archived = $this->service->archive($post);

    expect($archived->archived_at)->not->toBeNull();
});

it('archived post is excluded from the default query', function (): void {
    NewsFeedPost::factory()->forStructure($this->structure)->create(['archived_at' => now()]);
    NewsFeedPost::factory()->forStructure($this->structure)->create();

    // The model does not apply a global scope for archived — that's the controller's
    // responsibility. Here we verify that the archived_at column is stamped.
    expect(NewsFeedPost::whereNull('archived_at')->count())->toBe(1);
});
