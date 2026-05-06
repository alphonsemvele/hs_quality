<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\AuditStatus;
use App\Enums\Referentiel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Module 6 audit. Named QualityAudit (table `quality_audits`) to avoid
 * colliding with the `audits` table maintained by owen-it/laravel-auditing
 * for cross-cutting model-history tracking.
 */
class QualityAudit extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'quality_audits';

    protected $fillable = [
        'structure_id',
        'created_by',
        'titre',
        'referentiel',
        'description',
        'date_audit',
        'auditeur',
        'statut',
        'score',
        'finalized_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'date_audit' => 'date',
            'finalized_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'referentiel' => Referentiel::class,
            'statut' => AuditStatus::class,
            'score' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ecarts(): HasMany
    {
        return $this->hasMany(AuditEcart::class);
    }

    // ── State helpers ──────────────────────────────────────────────────────

    public function isTerminal(): bool
    {
        return $this->statut->isTerminal();
    }
}
