<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\PacStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $structure_id
 * @property string|null $audit_run_id
 * @property string $title
 * @property string|null $description
 * @property PacStatus $status
 * @property string|null $target_period
 * @property int|null $created_by
 * @property Carbon|null $closed_at
 * @property int|null $closed_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, PacAction> $actions
 * @property-read int|null $actions_count
 * @property-read AuditRun|null $auditRun
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\PacFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac whereAuditRunId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac whereClosedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac whereTargetPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pac withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Pac extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'pacs';

    protected $fillable = [
        'structure_id',
        'audit_run_id',
        'title',
        'description',
        'status',
        'target_period',
        'created_by',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PacStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    public function actions(): HasMany
    {
        return $this->hasMany(PacAction::class, 'pac_id');
    }

    public function auditRun(): BelongsTo
    {
        return $this->belongsTo(AuditRun::class, 'audit_run_id');
    }

    public function isDraft(): bool
    {
        return $this->status === PacStatus::Draft;
    }

    public function isClosed(): bool
    {
        return $this->status === PacStatus::Closed;
    }
}
