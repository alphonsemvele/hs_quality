<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\DataExportStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GDPR (article 15 / 20) personal data export request — one row per
 * "give me everything you have on me" click. Tenant-scoped so a request
 * from structure A is invisible to structure B. Archive lives on S3 for
 * 7 days then auto-expires (status flips to Expired and the file is
 * purged by GenerateDataExportJob's sibling cleanup job).
 */
class DataExportRequest extends Model
{
    use BelongsToStructure;
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'structure_id',
        'user_id',
        'status',
        'archive_disk',
        'archive_path',
        'archive_size_bytes',
        'processed_at',
        'expires_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => DataExportStatus::class,
            'processed_at' => 'datetime',
            'expires_at' => 'datetime',
            'archive_size_bytes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }
}
