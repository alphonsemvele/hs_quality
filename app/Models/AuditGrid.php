<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\AuditGridSource;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
}
