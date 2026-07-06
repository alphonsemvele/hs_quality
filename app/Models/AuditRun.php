<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\AuditRunStatus;
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
 * @property string $audit_grid_id
 * @property string $title
 * @property Carbon $run_date
 * @property AuditRunStatus $status
 * @property numeric|null $score
 * @property numeric|null $max_score
 * @property int|null $finalised_by
 * @property Carbon|null $finalised_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $pdf_path
 * @property Carbon|null $pdf_generated_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read AuditGrid|null $grid
 * @property-read Collection<int, AuditRunResponse> $responses
 * @property-read int|null $responses_count
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\AuditRunFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun whereAuditGridId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun whereFinalisedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun whereFinalisedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun whereMaxScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun wherePdfGeneratedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun wherePdfPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun whereRunDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRun withoutTrashed()
 *
 * @mixin \Eloquent
 */
class AuditRun extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'audit_runs';

    protected $fillable = [
        'structure_id',
        'audit_grid_id',
        'title',
        'run_date',
        'status',
        'score',
        'max_score',
        'finalised_by',
        'finalised_at',
        'pdf_path',
        'pdf_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'run_date' => 'date',
            'status' => AuditRunStatus::class,
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'finalised_at' => 'datetime',
            'pdf_generated_at' => 'datetime',
        ];
    }

    public function hasPdf(): bool
    {
        return $this->pdf_generated_at !== null && $this->pdf_path !== null;
    }

    public function grid(): BelongsTo
    {
        return $this->belongsTo(AuditGrid::class, 'audit_grid_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(AuditRunResponse::class, 'audit_run_id');
    }

    public function isDraft(): bool
    {
        return $this->status === AuditRunStatus::Draft;
    }

    public function isFinalised(): bool
    {
        return $this->status === AuditRunStatus::Finalised;
    }
}
