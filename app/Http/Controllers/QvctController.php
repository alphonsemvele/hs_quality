<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M3 — QVCT (Qualité de Vie et Conditions de Travail).
 *
 * Frontend pages are shipped; backend domain not yet implemented.
 */
class QvctController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard/qvct/index', [
            'campagnes' => [],
            'stats' => [
                'score_moyen' => null,
                'taux_participation' => null,
                'alertes_actives' => 0,
                'derniere_campagne' => null,
            ],
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
}
