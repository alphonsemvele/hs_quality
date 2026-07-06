<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
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
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property string|null $evidence_path
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
 * @method static \Database\Factories\HabilitationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation whereEvidencePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation whereValidFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation whereValidUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Habilitation withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Habilitation extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'habilitations';

    protected $fillable = [
        'structure_id',
        'user_id',
        'type',
        'reference_number',
        'valid_from',
        'valid_until',
        'evidence_path',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
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
}
