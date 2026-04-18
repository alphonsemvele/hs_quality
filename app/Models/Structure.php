<?php

namespace App\Models;

use App\Enums\StatutStructure;
use App\Enums\TierStructure;
use App\Enums\TypeStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Structure extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'nom',
        'type',
        'adresse',
        'siret',
        'tier',
        'statut',
    ];

    public function casts(): array
    {
        return [
            'type' => TypeStructure::class,
            'tier' => TierStructure::class,
            'statut' => StatutStructure::class,
        ];
    }

    public function isActive(): bool
    {
        return $this->statut === StatutStructure::Active;
    }

    public function hasFeature(string $feature): bool
    {
        return match ($feature) {
            'incidents.full' => in_array($this->tier, [TierStructure::Pro, TierStructure::Premium], true),
            'qvct' => in_array($this->tier, [TierStructure::Pro, TierStructure::Premium], true),
            'audits' => in_array($this->tier, [TierStructure::Pro, TierStructure::Premium], true),
            'portail_beneficiaires', 'ia_predictive' => $this->tier === TierStructure::Premium,
            default => true,
        };
    }
}
