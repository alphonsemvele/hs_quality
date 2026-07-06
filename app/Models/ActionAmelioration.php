<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\ActionStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property int $id
 * @property string $structure_id
 * @property string $plan_amelioration_id
 * @property string $description
 * @property string|null $responsable
 * @property Carbon|null $echeance
 * @property ActionStatus $statut
 * @property Carbon|null $realise_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read PlanAmelioration|null $plan
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\ActionAmeliorationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionAmelioration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionAmelioration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionAmelioration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionAmelioration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionAmelioration whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionAmelioration whereEcheance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionAmelioration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionAmelioration wherePlanAmeliorationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionAmelioration whereRealiseAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionAmelioration whereResponsable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionAmelioration whereStatut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionAmelioration whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActionAmelioration whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ActionAmelioration extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;

    protected $table = 'actions_amelioration';

    protected $fillable = [
        'structure_id',
        'plan_amelioration_id',
        'description',
        'responsable',
        'echeance',
        'statut',
        'realise_at',
    ];

    protected function casts(): array
    {
        return [
            'echeance' => 'date',
            'realise_at' => 'datetime',
            'statut' => ActionStatus::class,
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanAmelioration::class, 'plan_amelioration_id');
    }
}
