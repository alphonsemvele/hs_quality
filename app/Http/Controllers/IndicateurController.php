<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2/3 — Indicateurs & KPIs computed from M3 (QVCT), M5 (Formations),
 * and M6 (Audits) once those domains exist. Pure consumer; cannot ship until
 * the upstream models do.
 */
class IndicateurController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard/coming-soon', [
            'feature' => 'Indicateurs & KPIs',
            'feature_label' => 'Indicateurs & KPIs',
            'description' => 'Tableau de bord des indicateurs qualité (taux conformité, '
                .'complétion PAC, scores QVCT, expiration formations) avec séries temporelles '
                .'et seuils d\'alerte. Dépend des modules M3, M5, M6.',
            'eta' => 'Phase 2 — Mois 8 (T4 2026)',
            'tier_required' => 'pro',
        ]);
    }
}
