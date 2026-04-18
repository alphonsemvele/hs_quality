<?php

namespace App\Auditing;

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
 * See: references/audit-logging/tenant-scoped-driver.md
 */
class TenantAwareAudit extends Audit
{
    protected $table = 'audits';

    protected $fillable = [
        'structure_id',
        'user_type',
        'user_id',
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
        });
    }
}
