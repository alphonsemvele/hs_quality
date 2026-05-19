<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Intervention;
use App\Models\QualityAudit;
use App\Services\DashboardStatsService;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2/3 — Indicateurs & KPIs aggregated from M1 (interventions),
 * M2 (incidents), M3 (QVCT), M5 (formations) and M6 (audits / PAC).
 */
class IndicateurController extends Controller
{
    public function __construct(private readonly DashboardStatsService $stats) {}

    public function index(): Response
    {
        $user = request()->user();
        $structureId = $user->structure_id;
        $aggregates = $this->stats->stats($structureId);

        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        // Conformité: average score across all finalised audits in the DB.
        $conformite = QualityAudit::query()
            ->whereNotNull('score')
            ->avg('score');

        // Breakdown: incident counts by categorie.
        $incidentsByCategorie = Incident::query()
            ->selectRaw('categorie, count(*) as total')
            ->groupBy('categorie')
            ->get()
            ->map(fn ($r) => [
                'label' => $r->categorie,
                'value' => $r->total,
                'tone' => 'danger',
            ])
            ->values();

        // Breakdown: audit counts by referentiel.
        $auditsByReferentiel = QualityAudit::query()
            ->selectRaw('referentiel, count(*) as total')
            ->groupBy('referentiel')
            ->get()
            ->map(fn ($r) => [
                'label' => $r->referentiel,
                'value' => $r->total,
                'tone' => 'sage',
            ])
            ->values();

        // Monthly series: last 7 months of intervention + incident counts.
        $series = collect(range(6, 0))->map(function (int $offset) use ($today) {
            $month = $today->copy()->subMonths($offset)->startOfMonth();
            $end = $month->copy()->endOfMonth();

            return [
                'label' => $month->isoFormat('MMM YY'),
                'interventions' => Intervention::query()
                    ->whereBetween('planned_date', [$month->toDateString(), $end->toDateString()])
                    ->count(),
                'incidents' => Incident::query()
                    ->whereBetween('occurred_at', [$month, $end])
                    ->count(),
            ];
        })->values();

        return Inertia::render('dashboard/indicateurs/index', [
            'indicateurs' => [
                'conformite' => $conformite !== null ? (int) round((float) $conformite) : null,
                'pac_completion' => null,
                'qvct_moyen' => null,
                'formations_a_jour' => null,
                'incidents_ce_mois' => $aggregates['incidents_ce_mois'] ?? null,
                'interventions_ce_mois' => $aggregates['interventions_ce_mois'] ?? null,
            ],
            'series' => [
                [
                    'key' => 'interventions',
                    'label' => 'Interventions réalisées',
                    'tone' => 'neutral',
                    'data' => $series->map(fn ($m) => ['label' => $m['label'], 'value' => $m['interventions']]),
                ],
                [
                    'key' => 'incidents',
                    'label' => 'Incidents déclarés',
                    'tone' => 'danger',
                    'data' => $series->map(fn ($m) => ['label' => $m['label'], 'value' => $m['incidents']]),
                ],
            ],
            'breakdowns' => [
                'incidents_categorie' => $incidentsByCategorie,
                'audits_referentiel' => $auditsByReferentiel,
            ],
        ]);
    }
}
