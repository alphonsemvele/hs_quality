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
 * Corrective action on an Incident. Auditable per CDC §5.2 — modifications
 * to corrective plans must leave a who/when trail for HAS audits.
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
