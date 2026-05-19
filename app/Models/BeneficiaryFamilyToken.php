<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\FamilyTokenScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
