<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\EcartGravite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
