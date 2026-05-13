<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
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
