<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\AuditItemScale;
use App\Enums\ExigenceLevel;
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
 * @property string $audit_grid_id
 * @property string $title
 * @property string|null $description
 * @property AuditItemScale $scale
 * @property numeric $max_points
 * @property bool $evidence_required
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $axis_id
 * @property array<array-key, mixed>|null $sources
 * @property ExigenceLevel|null $level
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read AuditGridAxis|null $axis
 * @property-read AuditGrid|null $grid
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\AuditGridItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem whereAuditGridId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem whereAxisId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem whereEvidenceRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem whereMaxPoints($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem whereScale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem whereSources($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AuditGridItem extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $table = 'audit_grid_items';

    protected $fillable = [
        'structure_id',
        'audit_grid_id',
        'axis_id',
        'title',
        'description',
        'sources',
        'level',
        'scale',
        'max_points',
        'evidence_required',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'scale' => AuditItemScale::class,
            'level' => ExigenceLevel::class,
            'sources' => 'array',
            'max_points' => 'decimal:2',
            'evidence_required' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function grid(): BelongsTo
    {
        return $this->belongsTo(AuditGrid::class, 'audit_grid_id');
    }

    public function axis(): BelongsTo
    {
        return $this->belongsTo(AuditGridAxis::class, 'axis_id');
    }

    public function isImperatif(): bool
    {
        return $this->level === ExigenceLevel::Imperatif;
    }

    /**
     * Auto-priority derived from level + cotation, matching the
     * "Plan d'action" sheet of grille_evaluation_SAP.xlsx.
     */
    public function priorityFor(?string $cotation): string
    {
        if ($this->isImperatif() && ($cotation === 'C' || $cotation === 'D')) {
            return 'critique';
        }

        return match ($cotation) {
            'D' => 'elevee',
            'C' => 'moyenne',
            default => 'normale',
        };
    }
}
