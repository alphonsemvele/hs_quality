<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
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
 * @property string $audit_run_id
 * @property string $audit_grid_item_id
 * @property numeric|null $score
 * @property string|null $comment
 * @property string|null $evidence_url
 * @property int|null $recorded_by
 * @property Carbon|null $recorded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $cotation
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read AuditGridItem $item
 * @property-read AuditRun|null $run
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\AuditRunResponseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse whereAuditGridItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse whereAuditRunId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse whereCotation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse whereEvidenceUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse whereRecordedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditRunResponse whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AuditRunResponse extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $table = 'audit_run_responses';

    protected $fillable = [
        'structure_id',
        'audit_run_id',
        'audit_grid_item_id',
        'score',
        'cotation',
        'comment',
        'evidence_url',
        'recorded_by',
        'recorded_at',
    ];

    public function isNonApplicable(): bool
    {
        return $this->cotation === 'NA';
    }

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AuditRun::class, 'audit_run_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AuditGridItem::class, 'audit_grid_item_id');
    }
}
