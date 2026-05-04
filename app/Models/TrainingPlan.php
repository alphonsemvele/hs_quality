<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\TrainingPlanStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class TrainingPlan extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'training_plans';

    protected $fillable = [
        'structure_id',
        'year',
        'theme',
        'target_audience',
        'status',
        'created_by',
        'published_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'status' => TrainingPlanStatus::class,
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'training_plan_id');
    }
}
