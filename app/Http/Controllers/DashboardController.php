<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatsService;
use App\Support\ImpersonationSession;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardStatsService $statsService) {}

    public function index(): Response|RedirectResponse
    {
        $user = request()->user();

        // Platform operators have no tenant — send them to /admin where the
        // cross-tenant KPI surface lives. The /dashboard tenant view would be
        // empty for them (StructureScope returns 1=0 with no tenant context).
        // EXCEPT when they're inside an impersonation session: in that case
        // currentStructure() resolves to the impersonated tenant and the
        // dashboard renders as if they were that user.
        $impersonating = ImpersonationSession::current();

        if ($user->is_platform_admin === true && $impersonating === null) {
            return redirect()->route('admin.dashboard');
        }

        $structureId = $user->is_platform_admin
            ? (string) ($impersonating['structure_id'] ?? '')
            : (int) $user->structure_id;

        $stats = $this->statsService->stats($structureId);

        $incidents_recents = $this->statsService->recentIncidents()
            ->map(fn ($incident) => [
                'id' => $incident->id,
                'initials' => mb_strtoupper(
                    mb_substr($incident->declarant?->first_name ?? '?', 0, 1).
                    mb_substr($incident->declarant?->last_name ?? '?', 0, 1)
                ),
                'declarant' => trim(($incident->declarant?->first_name ?? '').' '.($incident->declarant?->last_name ?? '')),
                'categorie' => $incident->categorie->label(),
                'gravite' => $incident->gravite->value,
                'statut' => $incident->statut->value,
                'depuis' => $incident->occurred_at?->diffForHumans(),
            ]);

        // QVCT alerts and audit data remain placeholders until M5/M6 domains are built.
        $alertes_qvct = [];
        $audits_recents = [];

        return Inertia::render('dashboard/index', [
            'stats' => $stats,
            'incidents_recents' => $incidents_recents,
            'alertes_qvct' => $alertes_qvct,
            'audits_recents' => $audits_recents,
            'user_first_name' => $user->first_name ?? '',
            'structure_name' => $user->structure?->nom ?? '',
        ]);
    }
}
