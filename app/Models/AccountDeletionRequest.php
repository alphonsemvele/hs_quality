<?php

declare(strict_types=1);

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\AccountDeletionStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Persisted GDPR (article 17) erasure intent. Lives 30 days in `pending`
 * before the scheduled job runs the anonymisation. While pending the
 * user can flip it to `cancelled` and keep their account.
 *
 * Tenant-scoped via the global structure scope so a request issued in
 * structure A is invisible to structure B.
 *
 * @property string $id
 * @property string $structure_id
 * @property int $user_id
 * @property AccountDeletionStatus $status
 * @property Carbon $requested_at
 * @property Carbon $effective_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $processed_at
 * @property string|null $failure_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Structure|null $structure
 * @property-read User|null $user
 *
 * @method static \Database\Factories\AccountDeletionRequestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereEffectiveAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereFailureReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereRequestedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountDeletionRequest whereUserId($value)
 *
 * @mixin \Eloquent
 */
class AccountDeletionRequest extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'structure_id',
        'user_id',
        'status',
        'requested_at',
        'effective_at',
        'cancelled_at',
        'processed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => AccountDeletionStatus::class,
            'requested_at' => 'datetime',
            'effective_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function isPending(): bool
    {
        return $this->status === AccountDeletionStatus::Pending;
    }

    public function isCancellable(): bool
    {
        return $this->isPending() && ($this->effective_at?->isFuture() ?? false);
    }
}
