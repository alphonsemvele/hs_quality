<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DiscussionGroup;
use App\Models\User;

/**
 * Discussion group policy.
 *   view   — group member (any role) — checked via the members pivot
 *   create — messages.moderate (only coordinateurs+ can spin up groups)
 *   update — messages.moderate
 *   delete — messages.moderate
 */
class DiscussionGroupPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('messages.send');
    }

    public function view(User $user, DiscussionGroup $group): bool
    {
        return $group->members()->where('user_id', $user->id)->exists()
            || $user->hasPermissionTo('messages.moderate');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('messages.moderate');
    }

    public function update(User $user, DiscussionGroup $group): bool
    {
        return $user->hasPermissionTo('messages.moderate');
    }

    public function delete(User $user, DiscussionGroup $group): bool
    {
        return $user->hasPermissionTo('messages.moderate');
    }
}
