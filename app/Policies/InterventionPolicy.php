<?php

namespace App\Policies;

use App\Models\Intervention;
use App\Models\User;

class InterventionPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'interventions.view.own',
            'interventions.view.structure',
        ]);
    }

    public function view(User $user, Intervention $intervention): bool
    {
        if ($user->hasPermissionTo('interventions.view.structure')) {
            return true;
        }

        return $user->hasPermissionTo('interventions.view.own')
            && $intervention->intervenant_id === $user->id;
    }

    public function create(User $user): bool
    {
        // Planning a new intervention (assigning a scheduled visit to an
        // intervenant) is a coordinateur/dirigeant responsibility. The
        // interventions.create.own permission is reserved for the mobile
        // API (Phase 1 Month 4) where intervenants self-log ad-hoc visits.
        return $user->hasPermissionTo('interventions.update.team');
    }

    public function update(User $user, Intervention $intervention): bool
    {
        if ($intervention->isTerminal()) {
            return false;
        }

        if ($user->hasPermissionTo('interventions.update.team')) {
            return true;
        }

        return $user->hasPermissionTo('interventions.update.own')
            && $intervention->intervenant_id === $user->id;
    }

    public function checkIn(User $user, Intervention $intervention): bool
    {
        if (! $intervention->isPlanned()) {
            return false;
        }

        if ($user->hasPermissionTo('interventions.update.team')) {
            return true;
        }

        return $user->hasPermissionTo('interventions.update.own')
            && $intervention->intervenant_id === $user->id;
    }

    public function checkOut(User $user, Intervention $intervention): bool
    {
        if (! $intervention->isInProgress()) {
            return false;
        }

        if ($user->hasPermissionTo('interventions.update.team')) {
            return true;
        }

        return $user->hasPermissionTo('interventions.update.own')
            && $intervention->intervenant_id === $user->id;
    }

    public function cancel(User $user, Intervention $intervention): bool
    {
        if ($intervention->isTerminal()) {
            return false;
        }

        if ($user->hasPermissionTo('interventions.update.team')) {
            return true;
        }

        return $user->hasPermissionTo('interventions.update.own')
            && $intervention->intervenant_id === $user->id;
    }

    public function delete(User $user, Intervention $intervention): bool
    {
        return $user->hasPermissionTo('interventions.delete');
    }

    /**
     * Report submission is permitted while in_progress (live tour) and
     * after completion (post-checkout amendment — common when an
     * intervenant remembers a detail). Cancelled / missed visits never
     * accept a report (no visit happened).
     */
    public function submitReport(User $user, Intervention $intervention): bool
    {
        if (! $intervention->isInProgress() && ! $intervention->isCompleted()) {
            return false;
        }

        if ($user->hasPermissionTo('interventions.update.team')) {
            return true;
        }

        return $user->hasPermissionTo('interventions.update.own')
            && $intervention->intervenant_id === $user->id;
    }
}
