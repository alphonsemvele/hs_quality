<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\TrainingAttendanceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
