<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Signature collected at intervention end. Auditable per CDC §5.2 —
 * signatures are evidence of service delivery and must be tamper-tracked.
 *
 * @property string $id
 * @property string $structure_id
 * @property string $intervention_id
 * @property string $disk
 * @property string $path
 * @property string $signer_type
 * @property int|null $signed_by
 * @property Carbon $signed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Intervention|null $intervention
 * @property-read User|null $signer
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\InterventionSignatureFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionSignature newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionSignature newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionSignature query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionSignature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionSignature whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionSignature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionSignature whereInterventionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionSignature wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionSignature whereSignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionSignature whereSignedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionSignature whereSignerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionSignature whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionSignature whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class InterventionSignature extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'structure_id',
        'intervention_id',
        'disk',
        'path',
        'signer_type',
        'signed_by',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
