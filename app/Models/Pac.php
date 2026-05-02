<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\PacStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Pac extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'pacs';

    protected $fillable = [
        'structure_id',
        'audit_run_id',
        'title',
        'description',
        'status',
        'target_period',
        'created_by',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PacStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    public function actions(): HasMany
    {
        return $this->hasMany(PacAction::class, 'pac_id');
    }

    public function auditRun(): BelongsTo
    {
        return $this->belongsTo(AuditRun::class, 'audit_run_id');
    }

    public function isDraft(): bool
    {
        return $this->status === PacStatus::Draft;
    }

    public function isClosed(): bool
    {
        return $this->status === PacStatus::Closed;
    }
}
