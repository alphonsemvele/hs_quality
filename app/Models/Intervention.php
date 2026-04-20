<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\InterventionStatus;
use App\Enums\VisitMode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Intervention extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'structure_id',
        'intervenant_id',
        'beneficiary_id',
        'care_plan_id',
        'planned_date',
        'planned_start_time',
        'planned_end_time',
        'actual_start_at',
        'actual_end_at',
        'checkin_latitude',
        'checkin_longitude',
        'status',
        'visit_mode',
        'report_text',
        'report_voice_transcript',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'actual_start_at' => 'datetime',
            'actual_end_at' => 'datetime',
            'checkin_latitude' => 'decimal:8',
            'checkin_longitude' => 'decimal:8',
            'status' => InterventionStatus::class,
            'visit_mode' => VisitMode::class,
            'report_text' => 'encrypted',
            'report_voice_transcript' => 'encrypted',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function intervenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'intervenant_id');
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(CarePlan::class);
    }

    public function completedTasks(): HasMany
    {
        return $this->hasMany(InterventionCompletedTask::class);
    }

    // ── State helpers ──────────────────────────────────────────────────────

    public function isPlanned(): bool
    {
        return $this->status === InterventionStatus::Planned;
    }

    public function isInProgress(): bool
    {
        return $this->status === InterventionStatus::InProgress;
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    /** Duration in minutes between actual_start and actual_end, or null. */
    public function actualDurationMinutes(): ?int
    {
        if (! $this->actual_start_at || ! $this->actual_end_at) {
            return null;
        }

        return (int) $this->actual_start_at->diffInMinutes($this->actual_end_at);
    }
}
