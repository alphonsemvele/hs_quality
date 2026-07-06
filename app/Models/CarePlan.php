<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\CarePlanStatus;
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
 * CarePlan — personalized care program for one Beneficiary. A beneficiary
 * may have at most one ACTIVE plan (plus any number of archived versions
 * for history). Tasks attached via PlannedTask records describe the
 * recurring activities the plan prescribes.
 *
 * @property string $id
 * @property string $structure_id
 * @property string $beneficiary_id
 * @property int|null $created_by_user_id
 * @property string $title
 * @property string|null $objectives
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property CarePlanStatus $status
 * @property Carbon|null $archived_at
 * @property string|null $archived_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Beneficiary|null $beneficiary
 * @property-read User|null $createdBy
 * @property-read Structure|null $structure
 * @property-read Collection<int, PlannedTask> $tasks
 * @property-read int|null $tasks_count
 *
 * @method static \Database\Factories\CarePlanFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereArchivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereArchivedReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereBeneficiaryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereCreatedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereObjectives($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CarePlan withoutTrashed()
 *
 * @mixin \Eloquent
 */
class CarePlan extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'structure_id',
        'beneficiary_id',
        'created_by_user_id',
        'title',
        'objectives',
        'start_date',
        'end_date',
        'status',
        'archived_at',
        'archived_reason',
    ];

    public function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'archived_at' => 'datetime',
            'status' => CarePlanStatus::class,

            // Objectives may reference health context — encrypt at rest.
            'objectives' => 'encrypted',
        ];
    }

    protected array $auditInclude = [
        'beneficiary_id',
        'title',
        'start_date',
        'end_date',
        'status',
        'archived_at',
        'archived_reason',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(PlannedTask::class)->orderBy('task_order');
    }

    public function isActive(): bool
    {
        return $this->status === CarePlanStatus::Active;
    }

    public function isArchived(): bool
    {
        return $this->status === CarePlanStatus::Archived;
    }
}
