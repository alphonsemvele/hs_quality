<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\QvctWeakSignalType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $structure_id
 * @property string $campaign_id
 * @property string|null $team_tag
 * @property QvctWeakSignalType $signal_type
 * @property array<array-key, mixed> $details
 * @property int $severity
 * @property int|null $acknowledged_by
 * @property Carbon|null $acknowledged_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $acknowledgedBy
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read QvctCampaign|null $campaign
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\QvctWeakSignalFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal whereAcknowledgedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal whereAcknowledgedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal whereCampaignId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal whereSignalType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal whereTeamTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctWeakSignal whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class QvctWeakSignal extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $table = 'qvct_weak_signals';

    protected $fillable = [
        'structure_id',
        'campaign_id',
        'team_tag',
        'signal_type',
        'details',
        'severity',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'signal_type' => QvctWeakSignalType::class,
            'details' => 'array',
            'severity' => 'integer',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(QvctCampaign::class, 'campaign_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }
}
