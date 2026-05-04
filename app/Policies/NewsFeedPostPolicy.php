<?php

namespace App\Policies;

use App\Models\NewsFeedPost;
use App\Models\User;

/**
 * News-feed post policy.
 *   view     — any user (structure-wide news)
 *   create   — newsfeed.post (coord/dirigeant/RH only)
 *   update   — author OR newsfeed.post
 *   pin      — newsfeed.post
 *   archive  — newsfeed.post
 *   delete   — newsfeed.post
 */
class NewsFeedPostPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, NewsFeedPost $post): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('newsfeed.post');
    }

    public function update(User $user, NewsFeedPost $post): bool
    {
        return $post->author_id === $user->id
            || $user->hasPermissionTo('newsfeed.post');
    }

    public function pin(User $user, NewsFeedPost $post): bool
    {
        return $user->hasPermissionTo('newsfeed.post');
    }

    public function archive(User $user, NewsFeedPost $post): bool
    {
        return $user->hasPermissionTo('newsfeed.post');
    }

    public function delete(User $user, NewsFeedPost $post): bool
    {
        return $user->hasPermissionTo('newsfeed.post');
    }
}
