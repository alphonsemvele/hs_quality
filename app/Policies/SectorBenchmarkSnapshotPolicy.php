<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * SectorBenchmarkSnapshot is NOT tenant-scoped (it's cross-tenant by design),
 * so it cannot extend BasePolicy. Access is gated purely by permission.
 */
class SectorBenchmarkSnapshotPolicy
{
    /**
     * Dirigeants can view pre-generated snapshot data (benchmark.sector.view).
     * The live cross-tenant query (cross_tenant_benchmark.read) is separate
     * and restricted to platform admins — the snapshot is a safe read of one row.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('benchmark.sector.view')
            || (bool) $user->is_platform_admin;
    }

    /** Only platform admins can trigger new snapshots. */
    public function create(User $user): bool
    {
        return (bool) $user->is_platform_admin;
    }
}
