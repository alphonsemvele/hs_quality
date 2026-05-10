<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QvctQuestionnaire;
use App\Models\User;

/**
 * QVCT questionnaire = template owned by the structure. Managed by RH /
 * dirigeant / référent qualité (granted `qvct.questionnaire.manage`).
 * Anyone with `qvct.respond` may view a published template (so the
 * mobile client can render the form), but cannot edit it.
 */
class QvctQuestionnairePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'qvct.questionnaire.manage',
            'qvct.respond',
        ]);
    }

    public function view(User $user, QvctQuestionnaire $questionnaire): bool
    {
        if ($user->hasPermissionTo('qvct.questionnaire.manage')) {
            return true;
        }

        return $user->hasPermissionTo('qvct.respond') && $questionnaire->is_active;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('qvct.questionnaire.manage');
    }

    public function update(User $user, QvctQuestionnaire $questionnaire): bool
    {
        return $user->hasPermissionTo('qvct.questionnaire.manage');
    }

    public function delete(User $user, QvctQuestionnaire $questionnaire): bool
    {
        return $user->hasPermissionTo('qvct.questionnaire.manage');
    }
}
