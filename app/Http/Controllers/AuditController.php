<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M6 — HAS / AFNOR / ISO 9001 audit grids and conformity scoring.
 *
 * Frontend pages are shipped; backend domain (models, services, policies)
 * is not yet implemented. Controllers pass empty/default data so the UI
 * renders with proper empty states and is ready to wire up.
 */
class AuditController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard/audits/index', [
            'audits' => [],
            'stats' => [
                'total' => 0,
                'en_cours' => 0,
                'termines' => 0,
                'score_moyen' => null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('dashboard/audits/create', [
            'referentiels' => [
                ['value' => 'has', 'label' => 'HAS — Évaluation externe'],
                ['value' => 'afnor', 'label' => 'AFNOR NF X50-056'],
                ['value' => 'iso9001', 'label' => 'ISO 9001:2015'],
                ['value' => 'interne', 'label' => 'Audit interne personnalisé'],
            ],
        ]);
    }

    public function store(): RedirectResponse
    {
        // Phase 2 stub — will call AuditService::create()
        return back()->with('info', 'Module Audits en cours de développement.');
    }

    public function show(string $id): Response
    {
        return Inertia::render('dashboard/audits/show', [
            'audit' => null,
        ]);
    }

    public function update(string $id): RedirectResponse
    {
        return back()->with('info', 'Module Audits en cours de développement.');
    }

    public function finaliser(string $id): RedirectResponse
    {
        return back()->with('info', 'Module Audits en cours de développement.');
    }
}
