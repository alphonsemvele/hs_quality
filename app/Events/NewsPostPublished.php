<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\NewsFeedPost;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a new post is published to the structure's news feed.
 * Spec: PHASE2_PROGRESS.md M4.14. Frontend listens via Laravel Echo on
 * a private channel scoped to the structure.
 */
class NewsPostPublished implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly NewsFeedPost $post) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('structure.'.$this->post->structure_id.'.news'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->post->id,
            'structure_id' => $this->post->structure_id,
            'title' => $this->post->title,
            'author_id' => $this->post->author_id,
            'pinned' => $this->post->pinned,
            'created_at' => $this->post->created_at?->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'news.published';
    }
}
