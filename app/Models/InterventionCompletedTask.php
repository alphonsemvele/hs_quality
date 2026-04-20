<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterventionCompletedTask extends Model
{
    use BelongsToStructure;
    use HasUuids;

    protected $fillable = [
        'structure_id',
        'intervention_id',
        'planned_task_id',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }

    public function plannedTask(): BelongsTo
    {
        return $this->belongsTo(PlannedTask::class);
    }
}
