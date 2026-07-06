<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\InterventionStatus;
use App\Enums\VisitMode;
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
 * @property int $intervenant_id
 * @property string $beneficiary_id
 * @property string|null $care_plan_id
 * @property Carbon $planned_date
 * @property string|null $planned_start_time
 * @property string|null $planned_end_time
 * @property Carbon|null $actual_start_at
 * @property Carbon|null $actual_end_at
 * @property numeric|null $checkin_latitude
 * @property numeric|null $checkin_longitude
 * @property InterventionStatus $status
 * @property VisitMode $visit_mode
 * @property string|null $report_text
 * @property string|null $report_voice_transcript
 * @property string|null $cancellation_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Beneficiary|null $beneficiary
 * @property-read CarePlan|null $carePlan
 * @property-read Collection<int, InterventionCompletedTask> $completedTasks
 * @property-read int|null $completed_tasks_count
 * @property-read User|null $intervenant
 * @property-read Collection<int, InterventionPhoto> $photos
 * @property-read int|null $photos_count
 * @property-read Collection<int, InterventionSignature> $signatures
 * @property-read int|null $signatures_count
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\InterventionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereActualEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereActualStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereBeneficiaryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereCancellationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereCarePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereCheckinLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereCheckinLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereIntervenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention wherePlannedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention wherePlannedEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention wherePlannedStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereReportText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereReportVoiceTranscript($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention whereVisitMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Intervention withoutTrashed()
 *
 * @mixin \Eloquent
 */
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

    /**
     * Whitelist of fields that ARE audited. Encrypted health-data fields
     * (report_text, report_voice_transcript) are deliberately omitted —
     * including them would (a) bloat the audit table with ciphertext,
     * (b) leave it unreadable after an APP_KEY rotation, (c) duplicate
     * the encrypted source of truth.
     *
     * Wave 1 / C4. We use auditInclude (whitelist) rather than auditExclude
     * (blacklist) for parity with Beneficiary and to fail-closed: a new
     * column is silently NOT audited until it's explicitly added here,
     * which prevents accidental PHI leakage into the audit table.
     */
    protected $auditInclude = [
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
        'cancellation_reason',
    ];

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

    public function photos(): HasMany
    {
        return $this->hasMany(InterventionPhoto::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(InterventionSignature::class);
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

    public function isCompleted(): bool
    {
        return $this->status === InterventionStatus::Completed;
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
