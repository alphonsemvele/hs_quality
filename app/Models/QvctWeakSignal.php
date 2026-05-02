<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\QvctWeakSignalType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
