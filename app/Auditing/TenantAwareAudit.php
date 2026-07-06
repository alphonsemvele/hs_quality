<?php

namespace App\Auditing;

use App\Models\User;
use App\Support\ImpersonationSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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
 * When a platform admin is impersonating a tenant user (see
 * ImpersonationController), we additionally stamp `impersonator_id` with that
 * admin's user id so the regulator-facing audit trail can distinguish "the
 * tenant user did X" from "the platform operator did X while impersonating
 * them". `user_id` is the apparent actor (the logged-in user — i.e. the
 * platform admin), and the presence of `impersonator_id` flags the row as
 * belonging to an impersonation session. Without this column the two cases
 * would be indistinguishable.
 *
 * See: references/audit-logging/tenant-scoped-driver.md
 *
 * @property int $id
 * @property string|null $structure_id
 * @property string|null $user_type
 * @property int|null $user_id
 * @property string $event
 * @property string $auditable_type
 * @property string $auditable_id
 * @property array<array-key, mixed>|null $old_values
 * @property array<array-key, mixed>|null $new_values
 * @property string|null $url
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $tags
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $impersonator_id
 * @property-read Model|\Eloquent $auditable
 * @property-read Model|\Eloquent|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereAuditableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereAuditableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereImpersonatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereNewValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereOldValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TenantAwareAudit whereUserType($value)
 *
 * @mixin \Eloquent
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
                $user = Auth::user();

                if ($user instanceof User && $user->is_platform_admin === true && ImpersonationSession::current() !== null) {
                    $audit->impersonator_id = $user->getKey();
                }
            }
        });
    }
}
