<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToStructure;
use App\Enums\DataExportStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * GDPR (article 15 / 20) personal data export request — one row per
 * "give me everything you have on me" click. Tenant-scoped so a request
 * from structure A is invisible to structure B. Archive lives on S3 for
 * 7 days then auto-expires (status flips to Expired and the file is
 * purged by GenerateDataExportJob's sibling cleanup job).
 *
 * @property string $id
 * @property string $structure_id
 * @property int $user_id
 * @property DataExportStatus $status
 * @property string|null $archive_disk
 * @property string|null $archive_path
 * @property int|null $archive_size_bytes
 * @property Carbon|null $processed_at
 * @property Carbon|null $expires_at
 * @property string|null $failure_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Structure|null $structure
 * @property-read User|null $user
 *
 * @method static \Database\Factories\DataExportRequestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest whereArchiveDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest whereArchivePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest whereArchiveSizeBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest whereFailureReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest whereStructureId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DataExportRequest whereUserId($value)
 *
 * @mixin \Eloquent
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
