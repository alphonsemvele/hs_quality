<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\TaskFrequency;
use App\Scopes\StructureScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * PlannedTask — one recurring activity prescribed by a CarePlan
 * (e.g., morning hygiene, blood pressure check, evening medication).
 *
 * structure_id is denormalized from the parent care_plan for fast
 * tenant-scoped queries. The boot() listener guarantees the two are
 * always in sync; a unit test enforces the invariant.
 */
class PlannedTask extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'structure_id',
        'care_plan_id',
        'title',
        'description',
        'frequency',
        'frequency_details',
        'duration_minutes',
        'task_order',
        'mandatory',
    ];

    public function casts(): array
    {
        return [
            'frequency' => TaskFrequency::class,
            'frequency_details' => 'array',
            'duration_minutes' => 'integer',
            'task_order' => 'integer',
            'mandatory' => 'boolean',
        ];
    }

    protected array $auditInclude = [
        'care_plan_id',
        'title',
        'frequency',
        'duration_minutes',
        'task_order',
        'mandatory',
    ];

    /**
     * On create, mirror structure_id UNCONDITIONALLY from the parent
     * care_plan. We override even if the BelongsToStructure trait has
     * already set structure_id from the current tenant context — the
     * invariant is "task.structure_id == care_plan.structure_id", not
     * "task.structure_id == currentStructure()". The parent wins.
     */
    protected static function booted(): void
    {
        static::creating(function (self $task): void {
            if ($task->care_plan_id) {
                $parentStructureId = CarePlan::query()
                    ->withoutGlobalScope(StructureScope::class)
                    ->whereKey($task->care_plan_id)
                    ->value('structure_id');

                if ($parentStructureId) {
                    $task->structure_id = $parentStructureId;
                }
            }
        });
    }

    public function carePlan(): BelongsTo
    {
        return $this->belongsTo(CarePlan::class);
    }
}
