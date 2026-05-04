<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2/3 — Indicateurs & KPIs computed from M3 (QVCT), M5 (Formations),
 * and M6 (Audits) once those domains exist.
 */
class IndicateurController extends Controller
{
    public function index(): Response
    {
        $demo = config('app.env') === 'local';

        return Inertia::render('dashboard/indicateurs/index', [
            'indicateurs' => $demo
                ? [
                    'conformite' => 78,
                    'pac_completion' => 58,
                    'qvct_moyen' => 6.8,
                    'formations_a_jour' => 83,
                    'incidents_ce_mois' => 3,
                    'interventions_ce_mois' => 47,
                ]
                : [
                    'conformite' => null,
                    'pac_completion' => null,
                    'qvct_moyen' => null,
                    'formations_a_jour' => null,
                    'incidents_ce_mois' => null,
                    'interventions_ce_mois' => null,
                ],
            'series' => [],
        ]);
    }
}
