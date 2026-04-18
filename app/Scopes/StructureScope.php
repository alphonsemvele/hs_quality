<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope applied via the BelongsToStructure trait. Filters every query on
 * a tenant-scoped model to the currently authenticated user's structure.
 *
 * When no tenant context is bound (public routes, Artisan commands outside a
 * tenant), the scope returns zero rows — safe by default, never leaks.
 */
class StructureScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = currentStructure();

        if ($tenant === null) {
            // No tenant context — filter out everything rather than risk a leak.
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where(
            $model->qualifyColumn('structure_id'),
            $tenant->getKey(),
        );
    }
}
