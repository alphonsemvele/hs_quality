<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Habilitation;
use App\Models\User;

/**
 * Habilitation policy.
 *   view.own       — the user themselves
 *   view.team      — certifications.view.team (coordinateur)
 *   view.structure — certifications.view.structure (dirigeant, RH)
 *   create/update  — certifications.record (RH, dirigeant)
 *   delete (expire)— certifications.record
 */
class HabilitationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'certifications.view.own',
            'certifications.view.team',
            'certifications.view.structure',
        ]);
    }

    public function view(User $user, Habilitation $habilitation): bool
    {
        if ($habilitation->user_id === $user->id) {
            return $user->hasPermissionTo('certifications.view.own');
        }

        return $user->hasAnyPermission([
            'certifications.view.team',
            'certifications.view.structure',
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('certifications.record');
    }

    public function update(User $user, Habilitation $habilitation): bool
    {
        return $user->hasPermissionTo('certifications.record');
    }

    public function delete(User $user, Habilitation $habilitation): bool
    {
        return $user->hasPermissionTo('certifications.record');
    }
}
