<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\QvctExchangeAddresseeRole;
use App\Enums\QvctExchangeStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $structure_id
 * @property int $requester_id
 * @property QvctExchangeAddresseeRole $addressee_role
 * @property QvctExchangeStatus $status
 * @property string|null $message
 * @property int|null $accepted_by
 * @property Carbon|null $accepted_at
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $closed_at
 * @property string|null $closed_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $acceptedBy
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $requester
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\QvctExchangeRequestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereAcceptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereAcceptedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereAddresseeRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereClosedReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereRequesterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereScheduledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctExchangeRequest withoutTrashed()
 *
 * @mixin \Eloquent
 */
class QvctExchangeRequest extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'qvct_exchange_requests';

    protected $fillable = [
        'structure_id',
        'requester_id',
        'addressee_role',
        'status',
        'message',
        'accepted_by',
        'accepted_at',
        'scheduled_at',
        'closed_at',
        'closed_reason',
    ];

    protected function casts(): array
    {
        return [
            'message' => 'encrypted',
            'addressee_role' => QvctExchangeAddresseeRole::class,
            'status' => QvctExchangeStatus::class,
            'accepted_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * `message` deliberately excluded — encrypted ciphertext on each audit
     * row would defeat encryption and bloat the audit log.
     */
    protected $auditInclude = [
        'structure_id',
        'requester_id',
        'addressee_role',
        'status',
        'accepted_by',
        'accepted_at',
        'scheduled_at',
        'closed_at',
        'closed_reason',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function isPending(): bool
    {
        return $this->status === QvctExchangeStatus::Pending;
    }

    public function isClosed(): bool
    {
        return $this->status === QvctExchangeStatus::Closed;
    }
}
