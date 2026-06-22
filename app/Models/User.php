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
