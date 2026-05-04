<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

/**
 * Message policy. Service enforces lifecycle (5-minute edit window,
 * author-only edit). Policy gates the membership / moderation perimeter.
 *   view   — any user with messages.send (i.e. anyone in a discussion)
 *   create — messages.send (sending = creating a message)
 *   update — author OR messages.moderate
 *   delete — author OR messages.moderate
 */
class MessagePolicy extends BasePolicy
{
    public function view(User $user, Message $message): bool
    {
        return $user->hasPermissionTo('messages.send');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('messages.send');
    }

    public function update(User $user, Message $message): bool
    {
        return $message->author_id === $user->id
            || $user->hasPermissionTo('messages.moderate');
    }

    public function delete(User $user, Message $message): bool
    {
        return $message->author_id === $user->id
            || $user->hasPermissionTo('messages.moderate');
    }
}
