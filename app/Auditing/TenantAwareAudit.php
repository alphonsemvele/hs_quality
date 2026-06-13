<?php

namespace App\Auditing;

use App\Services\SuperAdminImpersonationService;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Models\Audit;

/**
 * Custom Audit model that auto-populates structure_id from the current tenant
 * context at write time, so every audit entry is tenant-scoped.
 *
 * Registered in config/audit.php as the 'implementation' class.
 *
 * Note: we do NOT apply BelongsToStructure here. Audit entries are queried
 * via relationship from the auditable model (e.g., $incident->audits),
 * which already goes through the tenant-scoped $incident. For direct audit
 * queries (admin dashboard, RGPD review flows), services responsible for
 * those paths explicitly filter by structure_id.
 *
 * When a platform admin operates a tenant via the "view as dirigeant" surface,
 * we additionally stamp `impersonator_id` with that admin's user id so the
 * regulator-facing audit trail can distinguish "the dirigeant did X" from "the
 * platform operator did X while standing in for the dirigeant". `user_id` is
 * the apparent actor (the logged-in user — i.e. the super-admin), and the
 * presence of `impersonator_id` flags the row as belonging to an impersonation
 * session. Without this column the two cases would be indistinguishable.
 *
 * See: references/audit-logging/tenant-scoped-driver.md
 */
class TenantAwareAudit extends Audit
{
    protected $table = 'audits';

    protected $fillable = [
        'structure_id',
        'user_type',
        'user_id',
        'impersonator_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $audit): void {
            if (! $audit->structure_id && $tenant = currentStructure()) {
                $audit->structure_id = $tenant->getKey();
            }

            if (! $audit->impersonator_id) {
                $impersonatorId = app(SuperAdminImpersonationService::class)
                    ->impersonatorId(Auth::user());

                if ($impersonatorId !== null) {
                    $audit->impersonator_id = $impersonatorId;
                }
            }
        });
    }
}
