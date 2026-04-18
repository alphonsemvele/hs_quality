<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
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
            'email_verified_at' => 'timestamp',
            'service_id' => 'integer',
            'date_embauche' => 'date',
            'password' => 'hashed',
        ];
    }
}
