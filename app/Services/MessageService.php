<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\MessagePosted;
use App\Models\DiscussionGroup;
use App\Models\Message;
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
}
