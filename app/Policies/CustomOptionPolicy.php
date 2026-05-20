<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CustomOption;
use App\Models\User;

class CustomOptionPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('options.manage');
    }

    public function update(User $user, CustomOption $customOption): bool
    {
        return $user->hasPermissionTo('options.manage');
    }

    public function delete(User $user, CustomOption $customOption): bool
    {
        return $user->hasPermissionTo('options.manage');
    }
}
