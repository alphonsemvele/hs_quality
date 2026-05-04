<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M3 — QVCT (Qualité de Vie et Conditions de Travail).
 */
class QvctController extends Controller
{
    public function index(): Response
    {
        $demo = config('app.env') === 'local';

        return Inertia::render('dashboard/qvct/index', [
            'campagnes' => $demo ? $this->demoCampagnes() : [],
            'stats' => $demo
                ? ['score_moyen' => 6.8, 'taux_participation' => 75, 'alertes_actives' => 2, 'derniere_campagne' => '2026-04-01']
                : ['score_moyen' => null, 'taux_participation' => null, 'alertes_actives' => 0, 'derniere_campagne' => null],
        ]);
    }

    public function questionnaire(): Response
    {
        return Inertia::render('dashboard/qvct/questionnaire', [
            'questions' => [],
            'campagne' => null,
        ]);
    }

    public function store(): RedirectResponse
    {
        return back()->with('info', 'Module QVCT en cours de développement.');
    }

    /** @return list<array<string, mixed>> */
    private function demoCampagnes(): array
    {
        return [
            [
                'id' => 'camp-001',
                'titre' => 'Baromètre QVCT — Avril 2026',
                'date_debut' => '2026-04-01',
                'date_fin' => '2026-04-15',
                'statut' => 'terminee',
                'statut_label' => 'Terminée',
                'taux_participation' => 75,
                'score_moyen' => 6.8,
                'nb_reponses' => 9,
                'nb_alertes' => 2,
            ],
            [
                'id' => 'camp-002',
                'titre' => 'Baromètre QVCT — Janvier 2026',
                'date_debut' => '2026-01-10',
                'date_fin' => '2026-01-24',
                'statut' => 'terminee',
                'statut_label' => 'Terminée',
                'taux_participation' => 83,
                'score_moyen' => 7.2,
                'nb_reponses' => 10,
                'nb_alertes' => 1,
            ],
            [
                'id' => 'camp-003',
                'titre' => 'Baromètre QVCT — Juillet 2026',
                'date_debut' => '2026-07-01',
                'date_fin' => null,
                'statut' => 'planifiee',
                'statut_label' => 'Planifiée',
                'taux_participation' => 0,
                'score_moyen' => null,
                'nb_reponses' => 0,
                'nb_alertes' => 0,
            ],
        ];
    }
}
