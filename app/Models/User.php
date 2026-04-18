<?php

namespace App\Models;

use App\Enums\Fonction;
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
        'name',
        'lastname',
        'email',
        'password',
        'telephone',
        'avatar',
        'matricule',
        'type',
        'specialite',
        'date_embauche',
        'statut',
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
            'type' => Fonction::class,
            'date_embauche' => 'date',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'erased_at' => 'datetime',
            // two_factor_secret and two_factor_recovery_codes are auto-encrypted
            // by Fortify's TwoFactorAuthenticatable trait — do not add casts here.
        ];
    }

    /**
     * The Structure (tenant) this user belongs to.
     *
     * Note: User does NOT apply the BelongsToStructure trait. User is the
     * authentication subject that TenantResolver READS to determine the
     * current tenant context. Applying the trait would create a circular
     * dependency (scope filters by tenant, tenant comes from the user).
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
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
