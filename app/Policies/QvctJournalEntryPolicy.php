<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QvctJournalEntry;
use App\Models\User;

/**
 * Privacy-first policy. The owner can always read/edit/delete their
 * own entries. RH can read an entry only when the user explicitly
 * checked `shared_with_rh = true` — that flag is the consent gate.
 *
 * Coordinateurs and dirigeants cannot read journal entries even if
 * shared (the explicit consent path is RH-only; opening it wider
 * would change the privacy contract surfaced to intervenants in the
 * mobile UI).
 */
class QvctJournalEntryPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        // List endpoints filter at the controller layer (own vs RH-shared).
        return $user->hasPermissionTo('qvct.respond')
            || $user->hasPermissionTo('qvct.alert.receive');
    }

    public function view(User $user, QvctJournalEntry $entry): bool
    {
        if ($entry->user_id === $user->id) {
            return true;
        }

        return $entry->shared_with_rh
            && $user->hasPermissionTo('qvct.alert.receive')
            && $user->hasPermissionTo('qvct.weak_signal.acknowledge'); // RH role only
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('qvct.respond');
    }

    public function update(User $user, QvctJournalEntry $entry): bool
    {
        // Only the author can edit their own entry; nobody else, ever.
        return $entry->user_id === $user->id;
    }

    public function delete(User $user, QvctJournalEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }
}
