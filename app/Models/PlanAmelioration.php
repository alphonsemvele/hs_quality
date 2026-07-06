<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\ActionStatus;
use App\Enums\PacSource;
use App\Enums\PlanAmeliorationStatus;
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
 * @property int|null $created_by
 * @property string $titre
 * @property PacSource $source
 * @property string|null $source_id
 * @property string|null $constat
 * @property string|null $responsable
 * @property Carbon|null $echeance
 * @property PlanAmeliorationStatus $statut
 * @property Carbon|null $closed_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, ActionAmelioration> $actions
 * @property-read int|null $actions_count
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $creator
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\PlanAmeliorationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereCancellationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereConstat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereEcheance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereResponsable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereStatut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereTitre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanAmelioration withoutTrashed()
 *
 * @mixin \Eloquent
 */
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
