<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M6 — Plans d'Amélioration Continue (PAC).
 *
 * Frontend pages are shipped; backend domain not yet implemented.
 */
class PlanAmeliorationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard/plans-amelioration/index', [
            'plans' => [],
            'stats' => [
                'total' => 0,
                'en_cours' => 0,
                'termines' => 0,
                'taux_completion' => null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('dashboard/plans-amelioration/create', [
            'sources' => [
                ['value' => 'audit', 'label' => 'Écart d\'audit'],
                ['value' => 'incident', 'label' => 'Incident / EI'],
                ['value' => 'qvct', 'label' => 'Alerte QVCT'],
                ['value' => 'reclamation', 'label' => 'Réclamation usager'],
                ['value' => 'autre', 'label' => 'Autre'],
            ],
        ]);
    }

    public function store(): RedirectResponse
    {
        return back()->with('info', 'Module Plans d\'amélioration en cours de développement.');
    }

    public function show(string $id): Response
    {
        return Inertia::render('dashboard/plans-amelioration/show', [
            'plan' => null,
        ]);
    }

    public function update(string $id): RedirectResponse
    {
        return back()->with('info', 'Module Plans d\'amélioration en cours de développement.');
    }
}
