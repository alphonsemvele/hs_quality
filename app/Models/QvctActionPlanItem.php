<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\QvctActionPlanItemStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
