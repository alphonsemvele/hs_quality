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
 * Corrective action on an Incident. Auditable per CDC §5.2 — modifications
 * to corrective plans must leave a who/when trail for HAS audits.
 *
 * @property string $id
 * @property string $structure_id
 * @property string $incident_id
 * @property string $description
 * @property int|null $responsable_id
 * @property Carbon|null $echeance
 * @property string $statut
 * @property Carbon|null $realise_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Incident|null $incident
 * @property-read User|null $responsable
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\IncidentActionCorrectiveFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentActionCorrective newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentActionCorrective newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentActionCorrective query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentActionCorrective whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentActionCorrective whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentActionCorrective whereEcheance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentActionCorrective whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentActionCorrective whereIncidentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentActionCorrective whereRealiseAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentActionCorrective whereResponsableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentActionCorrective whereStatut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentActionCorrective whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentActionCorrective whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class IncidentActionCorrective extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    /**
     * The migration creates `incident_actions_correctives` (both words plural,
     * matching French naming where "actions correctives" is the natural plural).
     * Eloquent's default inflector pluralises only the last word — without
     * this override it would query the wrong table.
     */
    protected $table = 'incident_actions_correctives';

    protected $fillable = [
        'structure_id',
        'incident_id',
        'description',
        'responsable_id',
        'echeance',
        'statut',
        'realise_at',
    ];

    protected function casts(): array
    {
        return [
            'echeance' => 'date',
            'realise_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function isDone(): bool
    {
        return $this->statut === 'done';
    }
}
