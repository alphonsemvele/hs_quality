<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only model over Laravel's framework-managed failed_jobs table.
 * Not a domain model — no structure_id / BelongsToStructure.
 * Used only by SystemHealthController for platform operator metrics.
 */
class FailedJob extends Model
{
    protected $table = 'failed_jobs';

    public $timestamps = false;

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'failed_at' => 'datetime',
            'payload' => 'array',
        ];
    }
}
