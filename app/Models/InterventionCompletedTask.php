<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $structure_id
 * @property string $intervention_id
 * @property string $planned_task_id
 * @property Carbon|null $completed_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Intervention|null $intervention
 * @property-read PlannedTask|null $plannedTask
 * @property-read Structure|null $structure
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionCompletedTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionCompletedTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionCompletedTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionCompletedTask whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionCompletedTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionCompletedTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionCompletedTask whereInterventionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionCompletedTask whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionCompletedTask wherePlannedTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionCompletedTask whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionCompletedTask whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
