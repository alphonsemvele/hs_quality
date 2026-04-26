<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M5 — Compétences & Formation (habilitations, certifications,
 * plans de formation).
 *
 * Currently a placeholder. Previously returned hardcoded sample data
 * including named individuals and certification statuses (Wave 1 / C1).
 */
class FormationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard/coming-soon', [
            'feature' => 'M5 Formations',
            'feature_label' => 'Formations',
            'description' => 'Suivi des habilitations et certifications, alertes d\'expiration, '
                .'plan de formation annuel, micro-learning e-learning et tableau de bord '
                .'compétences par équipe.',
            'eta' => 'Phase 2 — Mois 8 (T4 2026)',
            'tier_required' => 'pro',
        ]);
    }

    public function store(): Response
    {
        return $this->index();
    }

    public function update(): Response
    {
        return $this->index();
    }
}
