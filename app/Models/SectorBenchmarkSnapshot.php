<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable monthly cross-tenant benchmark snapshot.
 *
 * NOT tenant-scoped — this table is inherently cross-tenant; the
 * BelongsToStructure trait must NOT be applied here. Access is gated
 * by the 'cross_tenant_benchmark.read' permission in
 * CrossTenantQueryService and the API controller.
 *
 * One row per calendar month; the unique constraint on snapshot_month
 * enforces this at the DB level.
 */
class SectorBenchmarkSnapshot extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'sector_benchmark_snapshots';

    protected $fillable = [
        'snapshot_month',
        'interventions_data',
        'incidents_data',
        'qvct_data',
        'conformity_data',
        'generated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_month' => 'date',
            'interventions_data' => 'array',
            'incidents_data' => 'array',
            'qvct_data' => 'array',
            'conformity_data' => 'array',
        ];
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }
}
