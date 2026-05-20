<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\CustomOptionField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
