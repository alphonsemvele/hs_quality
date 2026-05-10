<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Certification;
use App\Models\User;

/**
 * Certification policy. Same permission shape as HabilitationPolicy —
 * the cert is what expires; the habilitation is what's awarded.
 */
class CertificationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'certifications.view.own',
            'certifications.view.team',
            'certifications.view.structure',
        ]);
    }

    public function view(User $user, Certification $certification): bool
    {
        if ($certification->user_id === $user->id) {
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

    public function update(User $user, Certification $certification): bool
    {
        return $user->hasPermissionTo('certifications.record');
    }

    public function delete(User $user, Certification $certification): bool
    {
        return $user->hasPermissionTo('certifications.record');
    }
}
