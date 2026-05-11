<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QaQuestion;
use App\Models\User;

/**
 * Q&A question policy.
 *   view/viewAny — anyone with messages.send (forum is part of the
 *                  team's communication surface)
 *   create        — messages.send
 *   update        — author OR messages.moderate
 *   delete        — author OR messages.moderate
 *   acceptAnswer  — author only (lifecycle invariant in QaService too)
 */
class QaQuestionPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('messages.send');
    }

    public function view(User $user, QaQuestion $question): bool
    {
        return $user->hasPermissionTo('messages.send');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('messages.send');
    }

    public function update(User $user, QaQuestion $question): bool
    {
        return $question->author_id === $user->id
            || $user->hasPermissionTo('messages.moderate');
    }

    public function delete(User $user, QaQuestion $question): bool
    {
        return $question->author_id === $user->id
            || $user->hasPermissionTo('messages.moderate');
    }

    public function acceptAnswer(User $user, QaQuestion $question): bool
    {
        return $question->author_id === $user->id;
    }
}
