<?php

namespace App\Models;

use App\Concerns\BelongsToStructure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
