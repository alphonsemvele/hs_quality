<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\PredictionStatus;
use App\Enums\PredictionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PredictionRequest extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $table = 'prediction_requests';

    protected $fillable = [
        'structure_id',
        'requested_by_user_id',
        'type',
        'status',
        'input_snapshot',
        'result',
        'error_message',
        'ml_job_id',
        'requested_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => PredictionType::class,
            'status' => PredictionStatus::class,
            'input_snapshot' => 'array',
            'result' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function isPending(): bool
    {
        return $this->status === PredictionStatus::EnAttente;
    }
}
