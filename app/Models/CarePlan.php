<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\CarePlanStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * CarePlan — personalized care program for one Beneficiary. A beneficiary
 * may have at most one ACTIVE plan (plus any number of archived versions
 * for history). Tasks attached via PlannedTask records describe the
 * recurring activities the plan prescribes.
 */
class CarePlan extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'structure_id',
        'beneficiary_id',
        'created_by_user_id',
        'title',
        'objectives',
        'start_date',
        'end_date',
        'status',
        'archived_at',
        'archived_reason',
    ];

    public function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'archived_at' => 'datetime',
            'status' => CarePlanStatus::class,

            // Objectives may reference health context — encrypt at rest.
            'objectives' => 'encrypted',
        ];
    }

    protected array $auditInclude = [
        'beneficiary_id',
        'title',
        'start_date',
        'end_date',
        'status',
        'archived_at',
        'archived_reason',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(PlannedTask::class)->orderBy('task_order');
    }

    public function isActive(): bool
    {
        return $this->status === CarePlanStatus::Active;
    }

    public function isArchived(): bool
    {
        return $this->status === CarePlanStatus::Archived;
    }
}
