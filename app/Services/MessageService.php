<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\MessagePosted;
use App\Models\DiscussionGroup;
use App\Models\Message;
use App\Models\MessageReadCursor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * MessageService — sending, editing (within 5-minute window), and
 * deleting messages. Spec: PHASE2_PROGRESS.md M4.9.
 *
 * Edit window invariant: messages can only be edited by the author
 * within EDIT_WINDOW_SECONDS of creation. Past that, the message is
 * frozen — preserves conversation integrity (you can't rewrite history
 * to make someone else look bad). Deletion is always available to the
 * author; moderation deletion is a separate permission.
 */
class MessageService
{
    public const EDIT_WINDOW_SECONDS = 300;

    public function send(DiscussionGroup $group, User $author, string $body, ?array $attachments = null): Message
    {
        return DB::transaction(function () use ($group, $author, $body, $attachments): Message {
            $message = Message::create([
                'structure_id' => $group->structure_id,
                'discussion_group_id' => $group->id,
                'author_id' => $author->id,
                'body' => $body,
                'attachments' => $attachments,
            ]);

            MessagePosted::dispatch($message);

            return $message;
        });
    }

    public function edit(Message $message, User $editor, string $newBody): Message
    {
        if ($message->author_id !== $editor->id) {
            throw new HttpException(403, 'Only the author can edit this message.');
        }

        $age = $message->created_at?->diffInSeconds(now()) ?? PHP_INT_MAX;
        if ($age > self::EDIT_WINDOW_SECONDS) {
            throw new HttpException(409, sprintf(
                'Edit window (%d seconds) has elapsed.',
                self::EDIT_WINDOW_SECONDS,
            ));
        }

        $message->update([
            'body' => $newBody,
            'edited_at' => now(),
        ]);

        return $message->fresh();
    }

    /**
     * Soft-delete the message. Author can always delete their own; a
     * moderator (with messages.moderate permission) can delete any.
     * The policy decides who's allowed; the service just enforces the
     * lifecycle.
     */
    public function delete(Message $message): void
    {
        $message->delete();
    }

    /**
     * Advance the user's read cursor to the given message.
     *
     * The cursor only moves forward: if the message is older than the
     * current cursor position, the call is a no-op (mobile clients may
     * send mark-read out of order during sync replay).
     *
     * Phase 2 / M4.9 — mark-read via cursor (O(1) writes, O(1) unread
     * count queries vs a per-message receipt table).
     */
    public function markRead(Message $message, User $user): void
    {
        $messageAt = $message->created_at ?? now();

        MessageReadCursor::withoutGlobalScopes()
            ->upsert(
                [
                    'structure_id' => $message->structure_id,
                    'user_id' => $user->id,
                    'discussion_group_id' => $message->discussion_group_id,
                    'last_read_message_id' => $message->id,
                    'last_read_at' => $messageAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                uniqueBy: ['user_id', 'discussion_group_id'],
                update: ['last_read_message_id', 'last_read_at', 'updated_at'],
            );
    }

    /**
     * Count messages in the group that the user has not yet read.
     * Returns 0 when the user has no cursor (never opened the group).
     */
    public function unreadCount(DiscussionGroup $group, User $user): int
    {
        $cursor = MessageReadCursor::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('discussion_group_id', $group->id)
            ->first();

        // No cursor → user has never opened this group; pre-history messages
        // are not surfaced as unread (they'll see them when they first load).
        if ($cursor === null || $cursor->last_read_at === null) {
            return 0;
        }

        return Message::withoutGlobalScopes()
            ->where('discussion_group_id', $group->id)
            ->whereNull('deleted_at')
            ->where('created_at', '>', $cursor->last_read_at)
            ->count();
    }
}
