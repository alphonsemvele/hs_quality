<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\User;

/**
 * Derives onboarding progress from real DB state — not from client-side
 * localStorage — so a user who clears storage or signs in from a fresh
 * device still sees an accurate checklist. The frontend may merge this
 * with its own localStorage cache (welcome/ready slides have no DB proxy
 * and stay client-driven), but anything backed by data here is the
 * source of truth.
 *
 * Read-only by design — never mutates state. Tenant scoping is enforced
 * by the BelongsToStructure global scope on the underlying models, so
 * a query issued from structure A never sees structure B's rows.
 */
class OnboardingService
{
    /**
     * Compute the completion state of each onboarding step for the given user.
     *
     * @return array{
     *   completed: list<string>,
     *   counts: array{users: int, beneficiaries: int},
     *   mfa_enrolled: bool,
     *   all_critical_done: bool,
     * }
     */
    public function snapshot(User $user): array
    {
        $structureId = $user->structure_id;

        $userCount = $structureId
            ? User::query()->where('structure_id', $structureId)->count()
            : 0;
        $beneficiaryCount = $structureId
            ? Beneficiary::query()->withoutGlobalScopes()->where('structure_id', $structureId)->count()
            : 0;

        $mfaEnrolled = $user->two_factor_confirmed_at !== null;
        $teamReady = $userCount >= 2;
        $beneficiarySeeded = $beneficiaryCount >= 1;

        $completed = [];
        if ($teamReady) {
            $completed[] = 'team';
        }
        if ($mfaEnrolled) {
            $completed[] = 'mfa';
        }
        if ($beneficiarySeeded) {
            $completed[] = 'beneficiary';
        }

        $allCritical = $teamReady && $mfaEnrolled && $beneficiarySeeded;

        return [
            'completed' => $completed,
            'counts' => [
                'users' => $userCount,
                'beneficiaries' => $beneficiaryCount,
            ],
            'mfa_enrolled' => $mfaEnrolled,
            'all_critical_done' => $allCritical,
        ];
    }
}
