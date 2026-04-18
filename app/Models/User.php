<?php

namespace App\Models;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'structure_id',
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
}
