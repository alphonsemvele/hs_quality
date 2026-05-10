<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

/**
 * Document library policy.
 *   view     — Document::isVisibleTo (role-ACL allow-list, empty = all)
 *   create   — documents.upload (coord/dirigeant/RH/référent qualité)
 *   update   — uploader OR documents.upload
 *   delete   — uploader OR documents.upload
 */
class DocumentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Document $document): bool
    {
        return $document->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('documents.upload');
    }

    public function update(User $user, Document $document): bool
    {
        return $document->uploaded_by === $user->id
            || $user->hasPermissionTo('documents.upload');
    }

    public function delete(User $user, Document $document): bool
    {
        return $document->uploaded_by === $user->id
            || $user->hasPermissionTo('documents.upload');
    }
}
