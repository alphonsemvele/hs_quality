<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\QvctCampaignStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $structure_id
 * @property string $questionnaire_id
 * @property string $title
 * @property Carbon $opens_at
 * @property Carbon $closes_at
 * @property QvctCampaignStatus $status
 * @property string|null $target_team
 * @property int|null $launched_by
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $launchedBy
 * @property-read QvctQuestionnaire|null $questionnaire
 * @property-read Collection<int, QvctResponse> $responses
 * @property-read int|null $responses_count
 * @property-read Structure|null $structure
 * @property-read Collection<int, QvctWeakSignal> $weakSignals
 * @property-read int|null $weak_signals_count
 *
 * @method static \Database\Factories\QvctCampaignFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign whereClosesAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign whereLaunchedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign whereOpensAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign whereQuestionnaireId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign whereTargetTeam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctCampaign withoutTrashed()
 *
 * @mixin \Eloquent
 */
class QvctCampaign extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'qvct_campaigns';

    protected $fillable = [
        'structure_id',
        'questionnaire_id',
        'title',
        'opens_at',
        'closes_at',
        'status',
        'target_team',
        'launched_by',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'date',
            'closes_at' => 'date',
            'closed_at' => 'datetime',
            'status' => QvctCampaignStatus::class,
        ];
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(QvctQuestionnaire::class, 'questionnaire_id');
    }

    public function launchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'launched_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QvctResponse::class, 'campaign_id');
    }

    public function weakSignals(): HasMany
    {
        return $this->hasMany(QvctWeakSignal::class, 'campaign_id');
    }

    public function isOpen(): bool
    {
        return $this->status === QvctCampaignStatus::Active;
    }
}
