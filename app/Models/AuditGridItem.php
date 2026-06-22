<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\AuditItemScale;
use App\Enums\ExigenceLevel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
