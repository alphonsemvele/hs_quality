<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\CategorieIncident;
use App\Enums\GraviteIncident;
use App\Enums\StatutIncident;
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
 * @property string $id
 * @property string $structure_id
 * @property int $declared_by
 * @property int|null $assigned_to
 * @property string|null $beneficiary_id
 * @property string|null $intervention_id
 * @property Carbon $occurred_at
 * @property CategorieIncident $categorie
 * @property GraviteIncident $gravite
 * @property StatutIncident $statut
 * @property string $description
 * @property string|null $lieu
 * @property bool $avec_deces
 * @property bool $avec_hospitalisation
 * @property bool $avec_blessure_physique
 * @property string|null $analyse_causes
 * @property Carbon|null $closed_at
 * @property Carbon|null $notifie_responsable_at
 * @property Carbon|null $notifie_ars_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, IncidentActionCorrective> $actionsCorrectives
 * @property-read int|null $actions_correctives_count
 * @property-read User|null $assignee
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Beneficiary|null $beneficiary
 * @property-read User|null $declarant
 * @property-read Intervention|null $intervention
 * @property-read Structure|null $structure
 * @property-read Collection<int, IncidentSuivi> $suivis
 * @property-read int|null $suivis_count
 *
 * @method static \Database\Factories\IncidentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereAnalyseCauses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereAvecBlessurePhysique($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereAvecDeces($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereAvecHospitalisation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereBeneficiaryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereCategorie($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereDeclaredBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereGravite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereInterventionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereLieu($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereNotifieArsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereNotifieResponsableAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereOccurredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereStatut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Incident withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Incident extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'structure_id',
        'declared_by',
        'assigned_to',
        'beneficiary_id',
        'intervention_id',
        'occurred_at',
        'categorie',
        'gravite',
        'statut',
        'description',
        'lieu',
        'avec_deces',
        'avec_hospitalisation',
        'avec_blessure_physique',
        'analyse_causes',
        'closed_at',
        'notifie_responsable_at',
        'notifie_ars_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'closed_at' => 'datetime',
            'notifie_responsable_at' => 'datetime',
            'notifie_ars_at' => 'datetime',
            'avec_deces' => 'boolean',
            'avec_hospitalisation' => 'boolean',
            'avec_blessure_physique' => 'boolean',
            'categorie' => CategorieIncident::class,
            'gravite' => GraviteIncident::class,
            'statut' => StatutIncident::class,
            'description' => 'encrypted',
        ];
    }

    /**
     * Encrypted free-text fields excluded from the audit trail.
     *
     * Wave 1 / C4. The audit row still records WHO changed the Incident
     * and WHEN, plus all non-encrypted fields (gravite, statut, lifecycle
     * timestamps). The encrypted ciphertext of `description` and
     * `analyse_causes` would just bloat the audit log without forensic
     * value (the encrypted column itself is the source of truth).
     */
    protected $auditExclude = [
        'description',
        'analyse_causes',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function declarant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }

    public function actionsCorrectives(): HasMany
    {
        return $this->hasMany(IncidentActionCorrective::class);
    }

    public function suivis(): HasMany
    {
        return $this->hasMany(IncidentSuivi::class);
    }

    // ── State helpers ──────────────────────────────────────────────────────

    public function isClosed(): bool
    {
        return $this->statut->isClosed();
    }

    public function requiresARSNotification(): bool
    {
        return $this->gravite->requiresARSNotification();
    }
}
