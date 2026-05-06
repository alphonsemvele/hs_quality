<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformStatsService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform-operator landing page. Shows cross-tenant KPIs, recent
 * provisionings, per-tenant activity table, and open critical incidents
 * across the whole platform.
 *
 * Gated by EnsureSuperAdmin middleware (registered in bootstrap/app.php)
 * — tenant-scoped users 404 here, the surface is invisible.
 */
class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly PlatformStatsService $stats,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/dashboard/index', [
            'kpis' => $this->stats->platformKpis(),
            'recent_structures' => $this->stats->recentStructures(),
            'structures_activity' => $this->stats->structuresWithActivity(),
            'critical_incidents' => $this->stats->recentCriticalIncidents(),
        ]);
    }
}
