<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\QvctActionPlanStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class QvctActionPlan extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'qvct_action_plans';

    protected $fillable = [
        'structure_id',
        'title',
        'description',
        'status',
        'target_quarter',
        'created_by',
        'published_by',
        'published_at',
        'closed_by',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QvctActionPlanStatus::class,
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(QvctActionPlanItem::class, 'action_plan_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === QvctActionPlanStatus::Draft;
    }

    public function isPublished(): bool
    {
        return $this->status === QvctActionPlanStatus::Published;
    }

    public function isClosed(): bool
    {
        return $this->status === QvctActionPlanStatus::Closed;
    }
}
