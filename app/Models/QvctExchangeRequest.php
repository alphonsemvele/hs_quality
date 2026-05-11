<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\QvctExchangeAddresseeRole;
use App\Enums\QvctExchangeStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
