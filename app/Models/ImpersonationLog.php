<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform-level audit record for every impersonation session.
 * NOT domain-scoped — deliberately NO BelongsToStructure trait.
 * structure_id column is present for HDS cross-tenant audit queries.
 */
class ImpersonationLog extends Model
{
    protected $fillable = [
        'impersonator_id',
        'impersonated_user_id',
        'structure_id',
        'reason',
        'ip_address',
        'user_agent',
        'started_at',
        'stopped_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'stopped_at' => 'datetime',
        ];
    }

    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonator_id');
    }

    public function impersonatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonated_user_id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function isActive(): bool
    {
        return $this->stopped_at === null;
    }
}
