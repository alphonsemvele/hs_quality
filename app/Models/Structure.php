<?php

namespace App\Models;

use App\Enums\StructureStatus;
use App\Enums\StructureTier;
use App\Enums\StructureType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class Structure extends Model
{
    use Billable;
    use HasFactory;
    use HasUuids;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'siret',
        'billing_email',
        'tier',
        'status',
    ];

    public function casts(): array
    {
        return [
            'type' => StructureType::class,
            'tier' => StructureTier::class,
            'status' => StructureStatus::class,
            'trial_ends_at' => 'datetime',
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

    /**
     * Cashier customer accessors — Stripe customer name = structure
     * name; contact email = billing_email (a stable inbox like
     * facturation@... that's not tied to any individual user account).
     */
    public function stripeName(): ?string
    {
        return $this->name;
    }

    public function stripeEmail(): ?string
    {
        return $this->billing_email;
    }
}
