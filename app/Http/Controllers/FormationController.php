<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M5 — Compétences & Formation (habilitations, certifications,
 * plans de formation).
 *
 * Frontend pages are shipped; backend domain not yet implemented.
 */
class FormationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard/formations/index', [
            'formations' => [],
            'stats' => [
                'total' => 0,
                'a_jour' => 0,
                'expirant_bientot' => 0,
                'expirees' => 0,
            ],
        ]);
    }

    public function store(): RedirectResponse
    {
        return back()->with('info', 'Module Formations en cours de développement.');
    }

    public function update(string $id): RedirectResponse
    {
        return back()->with('info', 'Module Formations en cours de développement.');
    }
}
