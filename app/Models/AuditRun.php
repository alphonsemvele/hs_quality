<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\AuditRunStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
