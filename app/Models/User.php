<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'structure_id',
        'name',
        'lastname',
        'email',
        'password',
        'telephone',
        'avatar',
        'matricule',
        'fonction',
        'specialite',
        'service_id',
        'date_embauche',
        'statut',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'email_verified_at' => 'datetime',
            'service_id' => 'integer',
            'date_embauche' => 'date',
            'password' => 'hashed',
        ];
    }

    /**
     * The Structure (tenant) this user belongs to.
     * Note: User does NOT use the BelongsToStructure trait — User is the
     * authentication subject that the trait READS to determine the tenant
     * context. Applying the trait to User would create a circular dependency.
     */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }
}
