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
 * Photo attached to an Intervention. Auditable per CDC §5.2 — uploads
 * and deletions of photos taken at a beneficiary's home must be tracked
 * (forensic + RGPD subject-access response).
 *
 * @property string $id
 * @property string $structure_id
 * @property string $intervention_id
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 * @property string|null $original_name
 * @property int $uploaded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Intervention|null $intervention
 * @property-read Structure|null $structure
 * @property-read User|null $uploader
 *
 * @method static \Database\Factories\InterventionPhotoFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto whereInterventionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto whereOriginalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto whereSizeBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InterventionPhoto whereUploadedBy($value)
 *
 * @mixin \Eloquent
 */
class InterventionPhoto extends Model implements AuditableContract
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
        'mime_type',
        'size_bytes',
        'original_name',
        'uploaded_by',
    ];

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
