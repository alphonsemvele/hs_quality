<?php

declare(strict_types=1);

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Manual satisfaction rating recorded by a coordinateur or RH after a visit
 * or pulse-check call. Encrypted free-text comment because beneficiaries
 * sometimes share emotional or medical context that should not appear
 * in plain text in the database (CDC §5.2 health-adjacent data).
 *
 * Phase 3 will hook beneficiary-facing satisfaction surveys onto the same
 * model so the rating history accrues from both sources.
 *
 * @property string $id
 * @property string $structure_id
 * @property string $beneficiary_id
 * @property string|null $intervention_id
 * @property int $score
 * @property string|null $comment
 * @property Carbon $rated_at
 * @property int|null $rated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Beneficiary|null $beneficiary
 * @property-read Intervention|null $intervention
 * @property-read User|null $ratedBy
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\BeneficiarySatisfactionRatingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating whereBeneficiaryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating whereInterventionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating whereRatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating whereRatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiarySatisfactionRating withoutTrashed()
 *
 * @mixin \Eloquent
 */
class BeneficiarySatisfactionRating extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'beneficiary_satisfaction_ratings';

    protected $fillable = [
        'structure_id',
        'beneficiary_id',
        'intervention_id',
        'score',
        'comment',
        'rated_at',
        'rated_by',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'rated_at' => 'date',
            // Free-text potentially revealing context — encrypted.
            'comment' => 'encrypted',
        ];
    }

    /**
     * Audit whitelist — `comment` is encrypted, so storing its ciphertext
     * delta in the audits table would be useless. We track the score + the
     * rater identity instead.
     *
     * @var list<string>
     */
    protected array $auditInclude = [
        'score',
        'rated_at',
        'rated_by',
        'intervention_id',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }

    public function ratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_by');
    }
}
