<?php

declare(strict_types=1);

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\PredictionStatus;
use App\Enums\PredictionType;
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
 * @property int $requested_by_user_id
 * @property PredictionType $type
 * @property PredictionStatus $status
 * @property array<array-key, mixed> $input_snapshot
 * @property array<array-key, mixed>|null $result
 * @property string|null $error_message
 * @property string|null $ml_job_id
 * @property Carbon $requested_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $requestedBy
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\PredictionRequestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest whereInputSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest whereMlJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest whereRequestedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest whereRequestedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PredictionRequest whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
