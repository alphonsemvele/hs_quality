<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\TaskFrequency;
use App\Scopes\StructureScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * PlannedTask — one recurring activity prescribed by a CarePlan
 * (e.g., morning hygiene, blood pressure check, evening medication).
 *
 * structure_id is denormalized from the parent care_plan for fast
 * tenant-scoped queries. The boot() listener guarantees the two are
 * always in sync; a unit test enforces the invariant.
 *
 * @property string $id
 * @property string $structure_id
 * @property string $care_plan_id
 * @property string $title
 * @property string|null $description
 * @property TaskFrequency $frequency
 * @property array<array-key, mixed>|null $frequency_details
 * @property int|null $duration_minutes
 * @property int $task_order
 * @property bool $mandatory
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read CarePlan|null $carePlan
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\PlannedTaskFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask whereCarePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask whereDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask whereFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask whereFrequencyDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask whereMandatory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask whereTaskOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlannedTask withoutTrashed()
 *
 * @mixin \Eloquent
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
