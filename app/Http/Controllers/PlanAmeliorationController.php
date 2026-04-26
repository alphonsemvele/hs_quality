<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M6 — Plans d'Amélioration Continue (PAC).
 *
 * Currently a placeholder. Previously returned hardcoded sample data
 * including named individuals (Wave 1 / C1 in the security sweep).
 */
class PlanAmeliorationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard/coming-soon', [
            'feature' => 'M6 Plans d\'Amélioration',
            'feature_label' => 'Plans d\'amélioration',
            'description' => 'Génération automatique des plans d\'action correctifs à partir '
                .'des incidents et des écarts d\'audit, suivi de l\'avancement, et clôture '
                .'avec preuves d\'efficacité.',
            'eta' => 'Phase 2 — Mois 6 (T3 2026)',
            'tier_required' => 'pro',
        ]);
    }
}
