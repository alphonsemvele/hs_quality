<?php

namespace App\Concerns;

use App\Models\Structure;
use App\Scopes\StructureScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant scoping trait for every domain model. Applies a global scope that
 * filters every query by the currently authenticated user's structure_id,
 * and auto-populates structure_id on create from the current tenant context.
 *
 * Mandatory on every domain model per the project's row-level tenancy rule.
 * See: references/tenancy/tenant-scoped-trait.md
 */
trait BelongsToStructure
{
    public static function bootBelongsToStructure(): void
    {
        static::addGlobalScope(new StructureScope);

        static::creating(function ($model) {
            if (! $model->structure_id && $tenant = currentStructure()) {
                $model->structure_id = $tenant->getKey();
            }
        });
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }
}
