<?php

declare(strict_types=1);

use App\Models\Structure;
use Illuminate\Support\Facades\Auth;

if (! function_exists('currentStructure')) {
    /**
     * Return the currently bound tenant Structure, or null if none is active.
     *
     * The TenantResolver middleware binds the structure as soon as it runs.
     * However, route model binding (SubstituteBindings) runs earlier in the
     * web pipeline — before TenantResolver has had a chance to set the
     * container instance. To keep the BelongsToStructure global scope
     * working during route binding, we fall back to the authenticated
     * user's structure when nothing has been bound yet (the session is
     * already started by StartSession by the time SubstituteBindings runs,
     * so auth resolution is safe).
     *
     * Returns null when there is no tenant context at all (public routes,
     * Artisan commands running centrally). The BelongsToStructure global
     * scope handles null by returning zero rows.
     */
    function currentStructure(): ?Structure
    {
        if (app()->bound('current_structure')) {
            return app('current_structure');
        }

        $user = Auth::user();

        if ($user === null || empty($user->structure_id)) {
            return null;
        }

        // Cache the resolution so subsequent calls in this request reuse it
        // without re-hitting the relation. TenantResolver will overwrite
        // this when it runs later in the pipeline (idempotent).
        $structure = $user->structure;

        if ($structure !== null) {
            app()->instance('current_structure', $structure);
        }

        return $structure;
    }
}
