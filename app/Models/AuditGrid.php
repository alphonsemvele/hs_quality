<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\AuditGridSource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $structure_id
 * @property string $title
 * @property string|null $description
 * @property AuditGridSource $source
 * @property array<array-key, mixed>|null $weight_scheme
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Collection<int, AuditGridItem> $items
 * @property-read int|null $items_count
 * @property-read Collection<int, AuditRun> $runs
 * @property-read int|null $runs_count
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\AuditGridFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid whereWeightScheme($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditGrid withoutTrashed()
 *
 * @mixin \Eloquent
 */
class AuditGrid extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'audit_grids';

    protected $fillable = [
        'structure_id',
        'title',
        'description',
        'source',
        'weight_scheme',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'source' => AuditGridSource::class,
            'weight_scheme' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(AuditGridItem::class, 'audit_grid_id')->orderBy('position');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AuditRun::class, 'audit_grid_id');
    }
}
