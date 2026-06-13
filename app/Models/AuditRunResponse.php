<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
