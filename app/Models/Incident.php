<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\CategorieIncident;
use App\Enums\GraviteIncident;
use App\Enums\StatutIncident;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
