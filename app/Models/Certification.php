<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Certification extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'certifications';

    protected $fillable = [
        'structure_id',
        'user_id',
        'type',
        'reference_number',
        'issued_on',
        'expires_at',
        'evidence_path',
        'last_alerted_at',
        'last_alert_window',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'expires_at' => 'date',
            'last_alerted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Days remaining until expiry. Negative when already expired.
     * Used by the alert windowing logic + dashboard tile.
     */
    public function daysUntilExpiry(?CarbonInterface $now = null): int
    {
        $now ??= now();

        return (int) $now->startOfDay()->diffInDays($this->expires_at, absolute: false);
    }
}
