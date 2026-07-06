<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\BeneficiaryStatus;
use App\Enums\CarePlanStatus;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Beneficiary — a person receiving home-care services from the structure.
 *
 * Tenant-scoped via BelongsToStructure (mandatory — no beneficiary is
 * visible outside its structure). Audit-logged because records touch
 * RGPD Art 9 special-category health data. Sensitive text columns are
 * encrypted at rest via Eloquent casts.
 *
 * @property string $id
 * @property string $structure_id
 * @property string $first_name
 * @property string $last_name
 * @property Carbon|null $date_of_birth
 * @property Gender|null $gender
 * @property string|null $address
 * @property string|null $postal_code
 * @property string|null $city
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $marital_status
 * @property string|null $gir
 * @property string|null $primary_doctor
 * @property string|null $primary_doctor_phone
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $emergency_contact_relationship
 * @property string|null $medical_notes
 * @property string|null $allergies
 * @property string|null $medical_history
 * @property string|null $current_treatments
 * @property BeneficiaryStatus $status
 * @property Carbon|null $admitted_at
 * @property Carbon|null $exited_at
 * @property string|null $exit_reason
 * @property Carbon|null $erased_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read CarePlan|null $activeCarePlan
 * @property-read Collection<int, User> $allAssignedIntervenants
 * @property-read int|null $all_assigned_intervenants_count
 * @property-read Collection<int, User> $assignedIntervenants
 * @property-read int|null $assigned_intervenants_count
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Collection<int, CarePlan> $carePlans
 * @property-read int|null $care_plans_count
 * @property-read int|null $age
 * @property-read Collection<int, IntervenantAssignment> $intervenantAssignments
 * @property-read int|null $intervenant_assignments_count
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\BeneficiaryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereAdmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereAllergies($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereCurrentTreatments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereDateOfBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereEmergencyContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereEmergencyContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereEmergencyContactRelationship($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereErasedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereExitReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereExitedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereGir($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereMaritalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereMedicalHistory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereMedicalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary wherePrimaryDoctor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary wherePrimaryDoctorPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Beneficiary withoutTrashed()
 *
 * @mixin \Eloquent
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
