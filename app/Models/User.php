<?php

namespace App\Models;

use App\Enums\UserType;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Support\ImpersonationSession;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string|null $structure_id
 * @property string $first_name
 * @property string|null $last_name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $phone
 * @property string|null $avatar
 * @property string|null $employee_number
 * @property UserType|null $type
 * @property string|null $specialty
 * @property Carbon|null $hired_at
 * @property string $status
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property Carbon|null $erased_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property bool $is_platform_admin
 * @property string|null $beneficiary_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Beneficiary> $allAssignedBeneficiaries
 * @property-read int|null $all_assigned_beneficiaries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Beneficiary> $assignedBeneficiaries
 * @property-read int|null $assigned_beneficiaries_count
 * @property-read Beneficiary|null $beneficiary
 * @property-read \Illuminate\Database\Eloquent\Collection<int, IntervenantAssignment> $intervenantAssignments
 * @property-read int|null $intervenant_assignments_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read Structure|null $structure
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBeneficiaryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmployeeNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereErasedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsPlatformAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSpecialty($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles {
        hasRole as spatieHasRole;
        hasPermissionTo as spatieHasPermissionTo;
    }
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * Per-instance memoisation for {@see impersonationTarget()} — avoids
     * re-reading the session and re-querying the target user on every
     * hasRole()/hasPermissionTo() call within the same request.
     */
    private ?self $impersonationTargetCache = null;

    private bool $impersonationTargetResolved = false;

    protected $fillable = [
        'structure_id',
        'beneficiary_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'avatar',
        'employee_number',
        'type',
        'specialty',
        'hired_at',
        'status',
        'is_platform_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'email_verified_at' => 'datetime',
            'type' => UserType::class,
            'hired_at' => 'date',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'erased_at' => 'datetime',
            'is_platform_admin' => 'boolean',
        ];
    }

    /**
     * The Structure (tenant) this user belongs to.
     *
     * User does NOT apply the BelongsToStructure trait. User is the
     * authentication subject TenantResolver READS to determine the
     * current tenant context — applying the trait would create a
     * circular dependency.
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    /** Only populated for UserType::BeneficiairePortal accounts. */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function isErased(): bool
    {
        return $this->erased_at !== null;
    }

    public function requiresMandatoryMfa(): bool
    {
        return $this->type?->requiresMfa() ?? false;
    }

    /**
     * Every intervenant assignment this user has (active + historical).
     */
    public function intervenantAssignments(): HasMany
    {
        return $this->hasMany(IntervenantAssignment::class);
    }

    /**
     * All beneficiaries ever assigned to this intervenant (active +
     * historical). For active-only, use assignedBeneficiaries().
     */
    public function allAssignedBeneficiaries(): BelongsToMany
    {
        return $this->belongsToMany(
            Beneficiary::class,
            'intervenant_assignments',
            'user_id',
            'beneficiary_id',
        )->withPivot(['assigned_at', 'unassigned_at', 'notes'])->withTimestamps();
    }

    /**
     * Currently-assigned beneficiaries (unassigned_at IS NULL).
     */
    public function assignedBeneficiaries(): BelongsToMany
    {
        return $this->allAssignedBeneficiaries()->wherePivotNull('unassigned_at');
    }

    /**
     * Spatie\Permission role check, with one extra branch: while a platform
     * admin is impersonating a tenant user (see {@see ImpersonationController}),
     * we delegate to that user's own roles instead of the admin's (the admin
     * holds none — `is_platform_admin` accounts are not assigned Spatie
     * roles). This grants exactly what the impersonated user would see —
     * not a generic elevated role — so a coordinateur's session looks like
     * a coordinateur, not a dirigeant.
     *
     * The check matches Spatie's signature exactly so policies, blade
     * directives, and any third-party code calling `$user->hasRole(...)`
     * continue to work without changes.
     *
     * @param  string|int|array|Role|Collection  $roles
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        if ($target = $this->impersonationTarget()) {
            return $target->spatieHasRole($roles, $guard);
        }

        return $this->spatieHasRole($roles, $guard);
    }

    /**
     * Same idea as {@see hasRole()} for fine-grained permissions: while
     * impersonating, the platform admin's permission checks are delegated
     * to the impersonated user.
     *
     * @param  string|int|Permission|\BackedEnum  $permission
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if ($target = $this->impersonationTarget()) {
            return $target->spatieHasPermissionTo($permission, $guardName);
        }

        return $this->spatieHasPermissionTo($permission, $guardName);
    }

    /**
     * The tenant user this platform admin is currently impersonating, or
     * null when not impersonating (or not a platform admin at all).
     * Memoised on the instance since hasRole/hasPermissionTo run on every
     * policy check in the request.
     */
    private function impersonationTarget(): ?self
    {
        if ($this->is_platform_admin !== true) {
            return null;
        }

        if ($this->impersonationTargetResolved) {
            return $this->impersonationTargetCache;
        }

        $this->impersonationTargetResolved = true;

        $payload = ImpersonationSession::current();

        if ($payload === null) {
            return $this->impersonationTargetCache = null;
        }

        return $this->impersonationTargetCache = self::query()->find($payload['user_id']);
    }
}
