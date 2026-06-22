<?php

namespace App\Models;

use App\Enums\UserType;
use App\Services\SuperAdminImpersonationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Traits\HasRoles;

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
     * admin is inside an active "view as dirigeant" impersonation session,
     * we report the dirigeant role as if it had been assigned. The real
     * persistence layer never gets a row written — this is purely a
     * request-scoped capability grant tied to the session, so logout (or
     * the 4h hard timeout in {@see SuperAdminImpersonationService}) revokes
     * it instantly.
     *
     * The check matches Spatie's signature exactly so policies, blade
     * directives, and any third-party code calling `$user->hasRole(...)`
     * continue to work without changes.
     *
     * @param  string|int|array|Role|Collection  $roles
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        if ($this->impersonatesAsDirigeant() && $this->roleArgumentIncludesDirigeant($roles)) {
            return true;
        }

        return $this->spatieHasRole($roles, $guard);
    }

    /**
     * Same idea as {@see hasRole()} for fine-grained permissions: while
     * impersonating, the super-admin reports every permission that the
     * canonical `dirigeant` Spatie role grants. The dirigeant permission
     * list is memoised on the request-scoped impersonation service to
     * keep policy hot paths cheap.
     *
     * @param  string|int|Permission|\BackedEnum  $permission
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if ($this->impersonatesAsDirigeant()) {
            $name = $this->permissionName($permission);

            if ($name !== null && in_array($name, app(SuperAdminImpersonationService::class)->dirigeantPermissionNames(), true)) {
                return true;
            }
        }

        return $this->spatieHasPermissionTo($permission, $guardName);
    }

    /**
     * True iff this user is the platform admin currently driving an
     * impersonation session AND the request's tenant context resolves
     * to the structure they selected — both checks matter, otherwise a
     * super-admin without an active session would silently inherit
     * dirigeant rights everywhere.
     */
    private function impersonatesAsDirigeant(): bool
    {
        if ($this->is_platform_admin !== true) {
            return false;
        }

        $service = app(SuperAdminImpersonationService::class);

        return $service->isActive();
    }

    /**
     * Spatie accepts roles as string, int, Role model, array of those, or a
     * Collection. We only need to know whether 'dirigeant' is in the set —
     * the rest stays parent's problem.
     *
     * @param  mixed  $roles
     */
    private function roleArgumentIncludesDirigeant($roles): bool
    {
        if (is_string($roles)) {
            foreach (explode('|', $roles) as $candidate) {
                if (trim($candidate) === 'dirigeant') {
                    return true;
                }
            }

            return false;
        }

        if (is_array($roles) || $roles instanceof Collection) {
            foreach ($roles as $role) {
                if ($this->roleArgumentIncludesDirigeant($role)) {
                    return true;
                }
            }

            return false;
        }

        if ($roles instanceof Role) {
            return $roles->name === 'dirigeant';
        }

        return false;
    }

    /**
     * @param  mixed  $permission
     */
    private function permissionName($permission): ?string
    {
        if (is_string($permission)) {
            return $permission;
        }

        if ($permission instanceof \BackedEnum) {
            $value = $permission->value;

            return is_string($value) ? $value : null;
        }

        if ($permission instanceof Permission) {
            return $permission->name;
        }

        return null;
    }
}
