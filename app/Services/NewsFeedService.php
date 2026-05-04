<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\NewsPostPublished;
use App\Models\NewsFeedPost;
use App\Models\Structure;
use App\Models\User;

/**
 * NewsFeedService — publishing, pinning, and archiving structure-wide
 * announcements. Spec: PHASE2_PROGRESS.md M4.10.
 *
 * Pinned posts surface at the top of the feed; archived posts are hidden
 * from the default query without being hard-deleted (audit trail).
 */
class NewsFeedService
{
    public function publish(Structure $structure, User $author, string $title, string $body): NewsFeedPost
    {
        $post = NewsFeedPost::create([
            'structure_id' => $structure->id,
            'author_id' => $author->id,
            'title' => $title,
            'body' => $body,
            'pinned' => false,
        ]);

        NewsPostPublished::dispatch($post);

        return $post;
    }

    public function pin(NewsFeedPost $post): NewsFeedPost
    {
        $post->update(['pinned' => true]);

        return $post->fresh();
    }

    public function unpin(NewsFeedPost $post): NewsFeedPost
    {
        $post->update(['pinned' => false]);

        return $post->fresh();
    }

    public function archive(NewsFeedPost $post): NewsFeedPost
    {
        $post->update(['archived_at' => now()]);

        return $post->fresh();
    }
}
