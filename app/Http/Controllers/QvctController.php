<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M3 — QVCT (Qualité de Vie et Conditions de Travail).
 *
 * Currently a placeholder. Previously returned hardcoded sample data
 * including named individuals with mental-health distress flags — that
 * constituted an RGPD Art 9 special-category data breach for any
 * authenticated user (Wave 1 / C1 in the security sweep).
 */
class QvctController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard/coming-soon', [
            'feature' => 'M3 Baromètre QVCT',
            'feature_label' => 'Baromètre QVCT',
            'description' => 'Baromètres QVCT anonymes, détection des signaux faibles, '
                .'cartographie des risques psychosociaux par équipe et alertes individualisées '
                .'au référent RH.',
            'eta' => 'Phase 2 — Mois 5 (T2 2026)',
            'tier_required' => 'pro',
        ]);
    }

    /**
     * Stub kept so existing routes don't 404; will be redesigned when M3 ships.
     */
    public function questionnaire(): Response
    {
        return $this->index();
    }

    public function store(): Response
    {
        return $this->index();
    }
}
