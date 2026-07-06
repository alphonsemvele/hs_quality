<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Axe thématique d'une grille d'audit. Sert au regroupement des items
 * pour les grilles structurées comme la grille unifiée SAP (10 axes,
 * de "Droits, bientraitance et éthique" à "Spécificités handicap").
 *
 * @property string $id
 * @property string $structure_id
 * @property string $audit_grid_id
 * @property string $code
 * @property string $title
 * @property string|null $description
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read AuditGrid|null $grid
 * @property-read Collection<int, AuditGridItem> $items
 * @property-read int|null $items_count
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\AuditGridAxisFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridAxis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridAxis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridAxis query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridAxis whereAuditGridId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridAxis whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridAxis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridAxis whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridAxis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridAxis wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridAxis whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridAxis whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGridAxis whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AuditGridAxis extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $table = 'audit_grid_axes';

    protected $fillable = [
        'structure_id',
        'audit_grid_id',
        'code',
        'title',
        'description',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function grid(): BelongsTo
    {
        return $this->belongsTo(AuditGrid::class, 'audit_grid_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AuditGridItem::class, 'axis_id')->orderBy('position');
    }
}
