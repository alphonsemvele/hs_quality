<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use Carbon\CarbonInterface;
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
 * @property int $user_id
 * @property string $type
 * @property string|null $reference_number
 * @property Carbon $issued_on
 * @property Carbon $expires_at
 * @property string|null $evidence_path
 * @property Carbon|null $last_alerted_at
 * @property string|null $last_alert_window
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $recorder
 * @property-read Structure|null $structure
 * @property-read User|null $user
 *
 * @method static \Database\Factories\CertificationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereEvidencePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereIssuedOn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereLastAlertWindow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereLastAlertedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Certification withoutTrashed()
 *
 * @mixin \Eloquent
 */
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
