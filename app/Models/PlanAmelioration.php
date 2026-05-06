<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\ActionStatus;
use App\Enums\PacSource;
use App\Enums\PlanAmeliorationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PlanAmelioration extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'plans_amelioration';

    protected $fillable = [
        'structure_id',
        'created_by',
        'titre',
        'source',
        'source_id',
        'constat',
        'responsable',
        'echeance',
        'statut',
        'closed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'echeance' => 'date',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'source' => PacSource::class,
            'statut' => PlanAmeliorationStatus::class,
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ActionAmelioration::class);
    }

    // ── State helpers ──────────────────────────────────────────────────────

    public function isTerminal(): bool
    {
        return $this->statut->isTerminal();
    }

    /**
     * Progression % computed from realised actions over total non-cancelled
     * actions. Returns 0 when there are no actions yet.
     */
    public function progression(): int
    {
        $actions = $this->relationLoaded('actions') ? $this->actions : $this->actions()->get();
        $relevant = $actions->where('statut', '!=', ActionStatus::Annulee);
        $total = $relevant->count();

        if ($total === 0) {
            return 0;
        }

        $done = $relevant->where('statut', ActionStatus::Realisee)->count();

        return (int) round(($done / $total) * 100);
    }
}
