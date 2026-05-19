<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\AnnualReportStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

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
