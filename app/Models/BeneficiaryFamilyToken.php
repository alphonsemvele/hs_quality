<?php

declare(strict_types=1);

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\FamilyTokenScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $structure_id
 * @property string $beneficiary_id
 * @property string $token_hash
 * @property array<array-key, mixed> $scope
 * @property string $issued_to_name
 * @property int $issued_by_user_id
 * @property Carbon $expires_at
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read Beneficiary|null $beneficiary
 * @property-read User|null $issuedBy
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\BeneficiaryFamilyTokenFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken whereBeneficiaryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken whereIssuedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken whereIssuedToName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken whereScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken whereTokenHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BeneficiaryFamilyToken whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class BeneficiaryFamilyToken extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $table = 'beneficiary_family_tokens';

    protected $fillable = [
        'structure_id',
        'beneficiary_id',
        'token_hash',
        'scope',
        'issued_to_name',
        'issued_by_user_id',
        'expires_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'scope' => 'array',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasScope(FamilyTokenScope $scope): bool
    {
        return in_array($scope->value, $this->scope ?? [], true);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }
}
