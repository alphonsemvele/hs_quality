<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QaAnswer;
use App\Models\User;

/**
 * Q&A answer policy.
 *   view   — messages.send
 *   create — messages.send (answer = creating an answer to a question)
 *   update — author OR messages.moderate
 *   delete — author OR messages.moderate
 *   vote   — messages.send
 */
class QaAnswerPolicy extends BasePolicy
{
    public function view(User $user, QaAnswer $answer): bool
    {
        return $user->hasPermissionTo('messages.send');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('messages.send');
    }

    public function update(User $user, QaAnswer $answer): bool
    {
        return $answer->author_id === $user->id
            || $user->hasPermissionTo('messages.moderate');
    }

    public function delete(User $user, QaAnswer $answer): bool
    {
        return $answer->author_id === $user->id
            || $user->hasPermissionTo('messages.moderate');
    }

    public function vote(User $user, QaAnswer $answer): bool
    {
        return $user->hasPermissionTo('messages.send');
    }
}
