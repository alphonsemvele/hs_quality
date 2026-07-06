<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\TrainingAttendanceStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $structure_id
 * @property string $training_session_id
 * @property int $user_id
 * @property TrainingAttendanceStatus $status
 * @property string|null $notes
 * @property int|null $recorded_by
 * @property Carbon|null $attended_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read TrainingSession|null $session
 * @property-read Structure|null $structure
 * @property-read User|null $user
 *
 * @method static \Database\Factories\TrainingAttendanceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance whereAttendedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance whereTrainingSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingAttendance whereUserId($value)
 *
 * @mixin \Eloquent
 */
class TrainingAttendance extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $table = 'training_attendances';

    protected $fillable = [
        'structure_id',
        'training_session_id',
        'user_id',
        'status',
        'notes',
        'recorded_by',
        'attended_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TrainingAttendanceStatus::class,
            'attended_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
