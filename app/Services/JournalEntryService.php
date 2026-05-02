<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QvctMood;
use App\Models\QvctJournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Business operations on QVCT journal entries. Spec: PHASE2_PROGRESS.md
 * M3.23 + CDC §M3 line "Optional emotional journal".
 *
 * Privacy contract:
 *   - write() always sets user_id from the supplied actor — callers
 *     CANNOT spoof another user's entry through a payload.
 *   - listForUser() always scopes to user_id = actor.
 *   - listSharedForRh() returns ONLY rows where shared_with_rh = true,
 *     for any RH user in the same tenant. NEVER call this without
 *     verifying the actor has qvct.alert.receive permission first
 *     (controller responsibility — service trusts its caller).
 */
class JournalEntryService
{
    public function write(User $author, string $body, QvctMood $mood, bool $sharedWithRh = false): QvctJournalEntry
    {
        return QvctJournalEntry::create([
            'structure_id' => $author->structure_id,
            'user_id' => $author->id,
            'body' => $body,
            'mood' => $mood->value,
            'mood_score' => $mood->score(),
            'shared_with_rh' => $sharedWithRh,
        ]);
    }

    /**
     * Toggle / set the sharing consent on an existing entry. The owner
     * is the only caller permitted to do this — enforced by the policy
     * before reaching this service.
     */
    public function setSharing(QvctJournalEntry $entry, bool $sharedWithRh): QvctJournalEntry
    {
        $entry->update(['shared_with_rh' => $sharedWithRh]);

        return $entry->fresh();
    }

    /**
     * @return Collection<int, QvctJournalEntry>
     */
    public function listForUser(User $user, int $limit = 50): Collection
    {
        return QvctJournalEntry::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, QvctJournalEntry>
     */
    public function listSharedForRh(int $limit = 50): Collection
    {
        return QvctJournalEntry::query()
            ->where('shared_with_rh', true)
            ->with(['user:id,first_name,last_name'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
