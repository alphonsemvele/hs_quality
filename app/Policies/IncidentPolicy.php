<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'incidents.view.own',
            'incidents.view.structure',
        ]);
    }

    public function view(User $user, Incident $incident): bool
    {
        if ($user->hasPermissionTo('incidents.view.structure')) {
            return true;
        }

        return $user->hasPermissionTo('incidents.view.own')
            && $incident->declared_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('incidents.declare');
    }

    public function update(User $user, Incident $incident): bool
    {
        if ($incident->isClosed()) {
            return false;
        }

        return $user->hasPermissionTo('incidents.analyze');
    }

    public function assign(User $user, Incident $incident): bool
    {
        if ($incident->isClosed()) {
            return false;
        }

        return $user->hasPermissionTo('incidents.analyze');
    }

    public function analyse(User $user, Incident $incident): bool
    {
        if ($incident->isClosed()) {
            return false;
        }

        return $user->hasPermissionTo('incidents.analyze');
    }

    public function close(User $user, Incident $incident): bool
    {
        if ($incident->isClosed()) {
            return false;
        }

        return $user->hasPermissionTo('incidents.close');
    }

    public function delete(User $user, Incident $incident): bool
    {
        return $user->hasPermissionTo('incidents.delete');
    }
}
