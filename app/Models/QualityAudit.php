<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\AuditStatus;
use App\Enums\Referentiel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Module 6 audit. Named QualityAudit (table `quality_audits`) to avoid
 * colliding with the `audits` table maintained by owen-it/laravel-auditing
 * for cross-cutting model-history tracking.
 *
 * @property string $id
 * @property string $structure_id
 * @property int|null $created_by
 * @property string $titre
 * @property Referentiel $referentiel
 * @property string|null $description
 * @property Carbon|null $date_audit
 * @property string|null $auditeur
 * @property AuditStatus $statut
 * @property int|null $score
 * @property Carbon|null $finalized_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $creator
 * @property-read Collection<int, AuditEcart> $ecarts
 * @property-read int|null $ecarts_count
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\QualityAuditFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereAuditeur($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereCancellationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereDateAudit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereFinalizedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereReferentiel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereStatut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereTitre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QualityAudit withoutTrashed()
 *
 * @mixin \Eloquent
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
