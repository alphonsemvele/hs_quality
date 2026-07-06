<?php

declare(strict_types=1);

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
use App\Enums\AnnualReportStatus;
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
 * @property int $year
 * @property AnnualReportStatus $status
 * @property string|null $pdf_path
 * @property Carbon|null $pdf_generated_at
 * @property int $requested_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $requestedBy
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\AnnualReportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnualReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnualReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnualReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnualReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnualReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnualReport wherePdfGeneratedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnualReport wherePdfPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnualReport whereRequestedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnualReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnualReport whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnualReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AnnualReport whereYear($value)
 *
 * @mixin \Eloquent
 */
class AnnualReport extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $table = 'annual_reports';

    protected $fillable = [
        'structure_id',
        'year',
        'status',
        'pdf_path',
        'pdf_generated_at',
        'requested_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'status' => AnnualReportStatus::class,
            'pdf_generated_at' => 'datetime',
        ];
    }

    public function hasPdf(): bool
    {
        return $this->pdf_path !== null && $this->pdf_generated_at !== null;
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
