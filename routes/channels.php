<?php

use Illuminate\Support\Facades\Broadcast;

// Private channel for a structure — only members of the same structure may subscribe.
Broadcast::channel('structure.{structureId}', function ($user, int $structureId): bool {
    return (int) $user->structure_id === $structureId;
});
