<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QvctCampaign;
use App\Models\User;

/**
 * QVCT campaign = scheduled instance of a questionnaire. Managers
 * (qvct.campaign.manage) launch + close campaigns. Respondents
 * (qvct.respond) need view to discover open campaigns to fill in.
 *
 * `respond` ability is the gate the mobile / web client checks before
 * letting the user submit a response. The policy itself does not check
 * the campaign's open/closed window — that's a service-layer pre-condition
 * (so a closed campaign returns 409, not 403; "you may submit but it's
 * too late" is a state error, not an authorization error).
 */
class QvctCampaignPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'qvct.campaign.manage',
            'qvct.respond',
        ]);
    }

    public function view(User $user, QvctCampaign $campaign): bool
    {
        return $user->hasAnyPermission([
            'qvct.campaign.manage',
            'qvct.respond',
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('qvct.campaign.manage');
    }

    public function update(User $user, QvctCampaign $campaign): bool
    {
        return $user->hasPermissionTo('qvct.campaign.manage');
    }

    public function close(User $user, QvctCampaign $campaign): bool
    {
        return $user->hasPermissionTo('qvct.campaign.manage');
    }

    public function delete(User $user, QvctCampaign $campaign): bool
    {
        return $user->hasPermissionTo('qvct.campaign.manage');
    }

    public function respond(User $user, QvctCampaign $campaign): bool
    {
        return $user->hasPermissionTo('qvct.respond');
    }
}
