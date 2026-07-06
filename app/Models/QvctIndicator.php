<?php

namespace App\Models;

use App\Auditing\TenantAwareAudit;
use App\Concerns\BelongsToStructure;
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
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property numeric|null $absenteeism_rate
 * @property numeric|null $turnover_rate
 * @property int|null $work_accidents_count
 * @property numeric|null $barometer_mean_score
 * @property string|null $notes
 * @property int|null $captured_by
 * @property Carbon|null $captured_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, TenantAwareAudit> $audits
 * @property-read int|null $audits_count
 * @property-read User|null $capturedBy
 * @property-read Structure|null $structure
 *
 * @method static \Database\Factories\QvctIndicatorFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator whereAbsenteeismRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator whereBarometerMeanScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator whereCapturedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator whereCapturedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator wherePeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator whereTurnoverRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QvctIndicator whereWorkAccidentsCount($value)
 *
 * @mixin \Eloquent
 */
class QvctIndicator extends Model implements AuditableContract
{
    use Auditable;
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $table = 'qvct_indicators';

    protected $fillable = [
        'structure_id',
        'period_start',
        'period_end',
        'absenteeism_rate',
        'turnover_rate',
        'work_accidents_count',
        'barometer_mean_score',
        'notes',
        'captured_by',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'absenteeism_rate' => 'decimal:2',
            'turnover_rate' => 'decimal:2',
            'work_accidents_count' => 'integer',
            'barometer_mean_score' => 'decimal:2',
            'captured_at' => 'datetime',
        ];
    }

    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }
}
