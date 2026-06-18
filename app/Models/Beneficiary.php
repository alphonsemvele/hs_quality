<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\BeneficiaryStatus;
use App\Enums\CarePlanStatus;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Beneficiary — a person receiving home-care services from the structure.
 *
 * Tenant-scoped via BelongsToStructure (mandatory — no beneficiary is
 * visible outside its structure). Audit-logged because records touch
 * RGPD Art 9 special-category health data. Sensitive text columns are
 * encrypted at rest via Eloquent casts.
 */
class Beneficiary extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'beneficiaries';

    protected $fillable = [
        'structure_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'address',
        'postal_code',
        'city',
        'phone',
        'email',
        'marital_status',
        'gir',
        'primary_doctor',
        'primary_doctor_phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'medical_notes',
        'allergies',
        'medical_history',
        'current_treatments',
        'status',
        'admitted_at',
        'exited_at',
        'exit_reason',
        'erased_at',
    ];

    public function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'admitted_at' => 'date',
            'exited_at' => 'date',
            'erased_at' => 'datetime',
            'gender' => Gender::class,
            'status' => BeneficiaryStatus::class,
            'gir' => 'string',

            // Health data — RGPD Art 9 — encrypted at rest per
            // references/compliance/encrypted-fields.md
            'medical_notes' => 'encrypted',
            'allergies' => 'encrypted',
            'medical_history' => 'encrypted',
            'current_treatments' => 'encrypted',
        ];
    }

    /**
     * Audit whitelist. Encrypted columns are excluded — audit would capture
     * ciphertext, and the audit purpose for these fields is "was it changed?"
     * (yes/no) rather than what changed.
     */
    protected array $auditInclude = [
        'first_name',
        'last_name',
        'date_of_birth',
        'address',
        'phone',
        'gir',
        'status',
        'primary_doctor',
        'emergency_contact_name',
        'admitted_at',
        'exited_at',
    ];

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function initials(): string
    {
        $first = mb_strtoupper(mb_substr((string) $this->first_name, 0, 1));
        $last = mb_strtoupper(mb_substr((string) $this->last_name, 0, 1));

        return $first.$last;
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    public function isErased(): bool
    {
        return $this->erased_at !== null;
    }

    public function carePlans(): HasMany
    {
        return $this->hasMany(CarePlan::class);
    }

    public function activeCarePlan(): HasOne
    {
        return $this->hasOne(CarePlan::class)
            ->where('status', CarePlanStatus::Active->value)
            ->latest('start_date');
    }

    /**
     * Every intervenant assignment for this beneficiary (active + historical).
     */
    public function intervenantAssignments(): HasMany
    {
        return $this->hasMany(IntervenantAssignment::class);
    }

    /**
     * All intervenants ever assigned (active + historical).
     */
    public function allAssignedIntervenants(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'intervenant_assignments',
            'beneficiary_id',
            'user_id',
        )->withPivot(['assigned_at', 'unassigned_at', 'notes'])->withTimestamps();
    }

    /**
     * Currently-assigned intervenants (active assignments only).
     */
    public function assignedIntervenants(): BelongsToMany
    {
        return $this->allAssignedIntervenants()->wherePivotNull('unassigned_at');
    }
}
