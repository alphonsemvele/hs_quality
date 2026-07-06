<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
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
 * @property string $training_plan_id
 * @property string $title
 * @property string|null $trainer_name
 * @property int|null $trainer_user_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property int $capacity
 * @property string|null $location
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TrainingAttendance> $attendances
 * @property-read int|null $attendances_count
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read TrainingPlan|null $plan
 * @property-read Structure|null $structure
 * @property-read User|null $trainer
 *
 * @method static \Database\Factories\TrainingSessionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession whereCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession whereTrainerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession whereTrainerUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession whereTrainingPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingSession withoutTrashed()
 *
 * @mixin \Eloquent
 */
class TrainingSession extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'training_sessions';

    protected $fillable = [
        'structure_id',
        'training_plan_id',
        'title',
        'trainer_name',
        'trainer_user_id',
        'starts_at',
        'ends_at',
        'capacity',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TrainingPlan::class, 'training_plan_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_user_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(TrainingAttendance::class, 'training_session_id');
    }
}
