<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\AuditItemScale;
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
        'title',
        'description',
        'scale',
        'max_points',
        'evidence_required',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'scale' => AuditItemScale::class,
            'max_points' => 'decimal:2',
            'evidence_required' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function grid(): BelongsTo
    {
        return $this->belongsTo(AuditGrid::class, 'audit_grid_id');
    }
}
