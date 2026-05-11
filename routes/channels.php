<?php

use App\Models\DiscussionGroup;
use App\Models\Intervention;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast channels — Reverb private-channel authorizers
|--------------------------------------------------------------------------
| Each closure runs once when a client tries to subscribe. Returning
| true authorises; false (or any falsy value) refuses the subscription
| and the broker drops the WebSocket message.
|
| All authorizers must enforce both: (a) the user is in the same tenant
| as the resource being subscribed to, AND (b) the user has the right
| to see the resource (membership for groups, ownership for interventions).
*/

// Structure-wide private channel (legacy — kept for any one-off broadcasts
// that aren't already covered by a more specific channel below).
Broadcast::channel('structure.{structureId}', function (User $user, string $structureId): bool {
    return (string) $user->structure_id === $structureId;
});

// News feed — `structure.{id}.news`. Spec: M4.14. Anyone in the structure
// receives news posts (no role gate; the news feed is structure-wide).
Broadcast::channel('structure.{structureId}.news', function (User $user, string $structureId): bool {
    return (string) $user->structure_id === $structureId;
});

// Discussion-group chat — `group.{id}`. Spec: M4.13. Subscriber must be
// in the same structure AND be a member of the group (defense in depth:
// the policy stack already enforces this for HTTP, but Reverb has its
// own auth surface).
Broadcast::channel('group.{groupId}', function (User $user, string $groupId): bool {
    $group = DiscussionGroup::query()->find($groupId);

    if ($group === null || (string) $group->structure_id !== (string) $user->structure_id) {
        return false;
    }

    return $group->members()->where('user_id', $user->id)->exists()
        || $user->hasPermissionTo('messages.moderate');
});

// Intervention real-time updates — `intervention.{id}`. Closes the Phase 1
// P1-D3 deferral on the broker auth side. Subscriber must be in the same
// tenant AND either be the assigned intervenant OR have the team-wide
// view permission.
Broadcast::channel('intervention.{interventionId}', function (User $user, string $interventionId): bool {
    $intervention = Intervention::query()->find($interventionId);

    if ($intervention === null || (string) $intervention->structure_id !== (string) $user->structure_id) {
        return false;
    }

    return $intervention->intervenant_id === $user->id
        || $user->hasAnyPermission([
            'interventions.view.team',
            'interventions.view.structure',
        ]);
});
