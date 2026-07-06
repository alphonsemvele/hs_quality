<?php

declare(strict_types=1);

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\CustomOptionField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property int $id
 * @property string $structure_id
 * @property CustomOptionField $field_key
 * @property string $value
 * @property string $label
 * @property int $sort_order
 * @property bool $is_active
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $creator
 * @property-read Structure|null $structure
 *
 * @method static Builder<static>|CustomOption active()
 * @method static \Database\Factories\CustomOptionFactory factory($count = null, $state = [])
 * @method static Builder<static>|CustomOption forField(string $fieldKey)
 * @method static Builder<static>|CustomOption newModelQuery()
 * @method static Builder<static>|CustomOption newQuery()
 * @method static Builder<static>|CustomOption onlyTrashed()
 * @method static Builder<static>|CustomOption query()
 * @method static Builder<static>|CustomOption whereCreatedAt($value)
 * @method static Builder<static>|CustomOption whereCreatedBy($value)
 * @method static Builder<static>|CustomOption whereDeletedAt($value)
 * @method static Builder<static>|CustomOption whereFieldKey($value)
 * @method static Builder<static>|CustomOption whereId($value)
 * @method static Builder<static>|CustomOption whereIsActive($value)
 * @method static Builder<static>|CustomOption whereLabel($value)
 * @method static Builder<static>|CustomOption whereSortOrder($value)
 * @method static Builder<static>|CustomOption whereStructureId($value)
 * @method static Builder<static>|CustomOption whereUpdatedAt($value)
 * @method static Builder<static>|CustomOption whereValue($value)
 * @method static Builder<static>|CustomOption withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|CustomOption withoutTrashed()
 *
 * @mixin \Eloquent
 */
class CustomOption extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'structure_id',
        'field_key',
        'value',
        'label',
        'sort_order',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'field_key' => CustomOptionField::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeForField(Builder $query, string $fieldKey): void
    {
        $query->where('field_key', $fieldKey);
    }
}
