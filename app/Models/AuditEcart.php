<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\EcartGravite;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property int $id
 * @property string $structure_id
 * @property string $quality_audit_id
 * @property string $critere
 * @property string $constat
 * @property EcartGravite $gravite
 * @property string|null $action_corrective
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read QualityAudit|null $audit
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\AuditEcartFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEcart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEcart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEcart query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEcart whereActionCorrective($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEcart whereConstat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEcart whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEcart whereCritere($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEcart whereGravite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEcart whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEcart whereQualityAuditId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEcart whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEcart whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AuditEcart extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;

    protected $table = 'audit_ecarts';

    protected $fillable = [
        'structure_id',
        'quality_audit_id',
        'critere',
        'constat',
        'gravite',
        'action_corrective',
    ];

    protected function casts(): array
    {
        return [
            'gravite' => EcartGravite::class,
        ];
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(QualityAudit::class, 'quality_audit_id');
    }
}
