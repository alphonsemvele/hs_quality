<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\TrainingPlanStatus;
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
 * @property int $year
 * @property string $theme
 * @property string|null $target_audience
 * @property TrainingPlanStatus $status
 * @property int|null $created_by
 * @property Carbon|null $published_at
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $creator
 * @property-read Collection<int, TrainingSession> $sessions
 * @property-read int|null $sessions_count
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\TrainingPlanFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan whereArchivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan whereTargetAudience($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan whereTheme($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan whereYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingPlan withoutTrashed()
 *
 * @mixin \Eloquent
 */
class TrainingPlan extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'training_plans';

    protected $fillable = [
        'structure_id',
        'year',
        'theme',
        'target_audience',
        'status',
        'created_by',
        'published_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'status' => TrainingPlanStatus::class,
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'training_plan_id');
    }
}
