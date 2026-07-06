<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Platform-level audit record for every impersonation session.
 *
 * NOT domain-scoped — deliberately NO BelongsToStructure trait.
 * structure_id column is present for HDS cross-tenant audit queries.
 *
 * @property int $id
 * @property int $impersonator_id
 * @property int $impersonated_user_id
 * @property string $structure_id
 * @property string $reason
 * @property string $ip_address
 * @property string|null $user_agent
 * @property Carbon $started_at
 * @property Carbon|null $stopped_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $impersonatedUser
 * @property-read User|null $impersonator
 * @property-read Structure|null $structure
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog whereImpersonatedUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog whereImpersonatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog whereStoppedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImpersonationLog whereUserAgent($value)
 *
 * @mixin \Eloquent
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
