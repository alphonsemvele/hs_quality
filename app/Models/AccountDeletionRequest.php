<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\AccountDeletionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persisted GDPR (article 17) erasure intent. Lives 30 days in `pending`
 * before the scheduled job runs the anonymisation. While pending the
 * user can flip it to `cancelled` and keep their account.
 *
 * Tenant-scoped via the global structure scope so a request issued in
 * structure A is invisible to structure B.
 */
class AccountDeletionRequest extends Model
{
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
