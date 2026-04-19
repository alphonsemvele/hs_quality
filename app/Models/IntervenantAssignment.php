<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
