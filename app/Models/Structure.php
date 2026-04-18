<?php

namespace App\Models;

use App\Enums\StructureStatus;
use App\Enums\StructureTier;
use App\Enums\StructureType;
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
        'name',
        'type',
        'address',
        'siret',
        'tier',
        'status',
    ];

    public function casts(): array
    {
        return [
            'type' => StructureType::class,
            'tier' => StructureTier::class,
            'status' => StructureStatus::class,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === StructureStatus::Active;
    }

    public function hasFeature(string $feature): bool
    {
        return match ($feature) {
            'incidents.full' => in_array($this->tier, [StructureTier::Pro, StructureTier::Premium], true),
            'qvct' => in_array($this->tier, [StructureTier::Pro, StructureTier::Premium], true),
            'audits' => in_array($this->tier, [StructureTier::Pro, StructureTier::Premium], true),
            'beneficiary_portal', 'ai_predictive' => $this->tier === StructureTier::Premium,
            default => true,
        };
    }
}
