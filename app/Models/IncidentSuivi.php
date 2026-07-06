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
 * Follow-up note on an Incident. Auditable per CDC §5.2 — every note touching
 * a health-data record must leave a who/when trail.
 *
 * @property string $id
 * @property string $structure_id
 * @property string $incident_id
 * @property int $author_id
 * @property string $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $author
 * @property-read Incident|null $incident
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\IncidentSuiviFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentSuivi newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentSuivi newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentSuivi query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentSuivi whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentSuivi whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentSuivi whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentSuivi whereIncidentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentSuivi whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentSuivi whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IncidentSuivi whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class IncidentSuivi extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'structure_id',
        'incident_id',
        'author_id',
        'note',
    ];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
