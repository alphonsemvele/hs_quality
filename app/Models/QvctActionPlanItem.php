<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\QvctActionPlanItemStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $structure_id
 * @property string $action_plan_id
 * @property string $title
 * @property string|null $description
 * @property int|null $responsible_user_id
 * @property Carbon|null $due_date
 * @property QvctActionPlanItemStatus $status
 * @property string|null $impact_measurement_target
 * @property string|null $impact_measurement_actual
 * @property Carbon|null $impact_measured_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read QvctActionPlan|null $actionPlan
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $responsibleUser
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\QvctActionPlanItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem whereActionPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem whereImpactMeasuredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem whereImpactMeasurementActual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem whereImpactMeasurementTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem whereResponsibleUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctActionPlanItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class QvctActionPlanItem extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $table = 'qvct_action_plan_items';

    protected $fillable = [
        'structure_id',
        'action_plan_id',
        'title',
        'description',
        'responsible_user_id',
        'due_date',
        'status',
        'impact_measurement_target',
        'impact_measurement_actual',
        'impact_measured_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'status' => QvctActionPlanItemStatus::class,
            'impact_measured_at' => 'datetime',
        ];
    }

    public function actionPlan(): BelongsTo
    {
        return $this->belongsTo(QvctActionPlan::class, 'action_plan_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
