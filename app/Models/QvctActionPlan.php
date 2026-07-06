<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\QvctActionPlanStatus;
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
 * @property string $title
 * @property string|null $description
 * @property QvctActionPlanStatus $status
 * @property string|null $target_quarter
 * @property int|null $created_by
 * @property int|null $published_by
 * @property Carbon|null $published_at
 * @property int|null $closed_by
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $createdBy
 * @property-read Collection<int, QvctActionPlanItem> $items
 * @property-read int|null $items_count
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\QvctActionPlanFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan whereClosedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan wherePublishedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan whereTargetQuarter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlan withoutTrashed()
 *
 * @mixin \Eloquent
 */
class QvctActionPlan extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'qvct_action_plans';

    protected $fillable = [
        'structure_id',
        'title',
        'description',
        'status',
        'target_quarter',
        'created_by',
        'published_by',
        'published_at',
        'closed_by',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QvctActionPlanStatus::class,
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(QvctActionPlanItem::class, 'action_plan_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === QvctActionPlanStatus::Draft;
    }

    public function isPublished(): bool
    {
        return $this->status === QvctActionPlanStatus::Published;
    }

    public function isClosed(): bool
    {
        return $this->status === QvctActionPlanStatus::Closed;
    }
}
