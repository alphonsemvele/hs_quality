<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Photo attached to an Intervention. Auditable per CDC §5.2 — uploads
 * and deletions of photos taken at a beneficiary's home must be tracked
 * (forensic + RGPD subject-access response).
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
