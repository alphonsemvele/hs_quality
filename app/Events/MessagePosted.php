<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Real-time message-posted event. Spec: PHASE2_PROGRESS.md M4.13 +
 * CDC §M4 line "Secure instant messaging" — frontend listens via
 * Laravel Echo on a private channel scoped to the discussion group.
 */
class MessagePosted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Message $message) {}

    /**
     * Broadcast on a private channel scoped to the group; the channel
     * authorizer (broadcasts/channels.php — to be added in a future
     * slice) will gate by group membership.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('group.'.$this->message->discussion_group_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'discussion_group_id' => $this->message->discussion_group_id,
            'author_id' => $this->message->author_id,
            'created_at' => $this->message->created_at?->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.posted';
    }
}
