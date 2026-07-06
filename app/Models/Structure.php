<?php

namespace App\Models;

use App\Enums\StructureStatus;
use App\Enums\StructureTier;
use App\Enums\StructureType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Subscription;

/**
 * @property string $id
 * @property string $code
 * @property string $name
 * @property StructureType $type
 * @property string|null $address
 * @property string|null $siret
 * @property StructureTier $tier
 * @property StructureStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property Carbon|null $trial_ends_at
 * @property string|null $billing_email
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 *
 * @method static \Database\Factories\StructureFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure hasExpiredGenericTrial()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure onGenericTrial()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereBillingEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure wherePmLastFour($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure wherePmType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereSiret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereStripeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereTier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereTrialEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Structure withoutTrashed()
 *
 * @mixin \Eloquent
 */
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
