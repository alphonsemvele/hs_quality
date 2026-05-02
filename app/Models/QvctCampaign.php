<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\QvctCampaignStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
