<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Axe thématique d'une grille d'audit. Sert au regroupement des items
 * pour les grilles structurées comme la grille unifiée SAP (10 axes,
 * de "Droits, bientraitance et éthique" à "Spécificités handicap").
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
