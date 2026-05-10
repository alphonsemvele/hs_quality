<?php

declare(strict_types=1);

use App\Models\Structure;

if (! function_exists('currentStructure')) {
    /**
     * Return the currently bound tenant Structure, or null if none is active.
     *
     * Bound by TenantResolver middleware on authenticated web/API requests,
     * and by TenantAwareJob trait on queued jobs. Returns null in contexts
     * without a tenant (public routes, Artisan commands running centrally).
     * The BelongsToStructure global scope handles null by returning zero rows.
     */
    function currentStructure(): ?Structure
    {
        return app()->bound('current_structure')
            ? app('current_structure')
            : null;
    }
}
