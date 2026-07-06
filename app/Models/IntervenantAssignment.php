<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * IntervenantAssignment — a historical record of one intervenant assigned
 * to one beneficiary. Current (active) assignments have unassigned_at NULL;
 * historical rows record the window during which the assignment was active.
 *
 * The IntervenantAssignmentService enforces the invariant
 * "assignment.structure_id == user.structure_id == beneficiary.structure_id"
 * — a tested invariant, not a DB constraint.
 *
 * @property string $id
 * @property string $structure_id
 * @property int $user_id
 * @property string $beneficiary_id
 * @property int|null $assigned_by_user_id
 * @property Carbon $assigned_at
 * @property Carbon|null $unassigned_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $assignedBy
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Beneficiary|null $beneficiary
 * @property-read User|null $intervenant
 * @property-read Structure|null $structure
 *
 * @method static Builder<static>|IntervenantAssignment active()
 * @method static \Database\Factories\IntervenantAssignmentFactory factory($count = null, $state = [])
 * @method static Builder<static>|IntervenantAssignment inactive()
 * @method static Builder<static>|IntervenantAssignment newModelQuery()
 * @method static Builder<static>|IntervenantAssignment newQuery()
 * @method static Builder<static>|IntervenantAssignment query()
 * @method static Builder<static>|IntervenantAssignment whereAssignedAt($value)
 * @method static Builder<static>|IntervenantAssignment whereAssignedByUserId($value)
 * @method static Builder<static>|IntervenantAssignment whereBeneficiaryId($value)
 * @method static Builder<static>|IntervenantAssignment whereCreatedAt($value)
 * @method static Builder<static>|IntervenantAssignment whereId($value)
 * @method static Builder<static>|IntervenantAssignment whereNotes($value)
 * @method static Builder<static>|IntervenantAssignment whereStructureId($value)
 * @method static Builder<static>|IntervenantAssignment whereUnassignedAt($value)
 * @method static Builder<static>|IntervenantAssignment whereUpdatedAt($value)
 * @method static Builder<static>|IntervenantAssignment whereUserId($value)
 *
 * @mixin \Eloquent
 */
class IntervenantAssignment extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'structure_id',
        'user_id',
        'beneficiary_id',
        'assigned_by_user_id',
        'assigned_at',
        'unassigned_at',
        'notes',
    ];

    public function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
    }

    protected array $auditInclude = [
        'user_id',
        'beneficiary_id',
        'assigned_by_user_id',
        'assigned_at',
        'unassigned_at',
        'notes',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function intervenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    // -------------------------------------------------------------------------
    // Scopes + helpers
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unassigned_at');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->whereNotNull('unassigned_at');
    }

    public function isActive(): bool
    {
        return $this->unassigned_at === null;
    }
}
