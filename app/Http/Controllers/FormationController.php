<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M5 — Compétences & Formation.
 */
class FormationController extends Controller
{
    public function index(): Response
    {
        $demo = config('app.env') === 'local';

        return Inertia::render('dashboard/formations/index', [
            'formations' => $demo ? $this->demoFormations() : [],
            'stats' => $demo
                ? ['total' => 8, 'a_jour' => 5, 'expirant_bientot' => 2, 'expirees' => 1]
                : ['total' => 0, 'a_jour' => 0, 'expirant_bientot' => 0, 'expirees' => 0],
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

    /** @return list<array<string, mixed>> */
    private function demoFormations(): array
    {
        return [
            ['id' => 'f1', 'intervenant' => 'Marie Leclerc', 'initials' => 'ML', 'intitule' => 'Aide à la toilette et soins d\'hygiène', 'organisme' => 'INRS', 'date_obtention' => '2025-03-15', 'date_expiration' => '2027-03-15', 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Habilitation'],
            ['id' => 'f2', 'intervenant' => 'Marie Leclerc', 'initials' => 'ML', 'intitule' => 'Gestes et postures — Manutention', 'organisme' => 'PRAP', 'date_obtention' => '2024-11-20', 'date_expiration' => '2026-11-20', 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Certification'],
            ['id' => 'f3', 'intervenant' => 'Luc Moreau', 'initials' => 'LM', 'intitule' => 'PSC1 — Premiers secours', 'organisme' => 'Croix-Rouge', 'date_obtention' => '2024-06-10', 'date_expiration' => '2026-06-10', 'statut' => 'expire_bientot', 'statut_label' => 'Expire bientôt', 'type' => 'Certification'],
            ['id' => 'f4', 'intervenant' => 'Luc Moreau', 'initials' => 'LM', 'intitule' => 'Accompagnement Alzheimer', 'organisme' => 'France Alzheimer', 'date_obtention' => '2025-09-01', 'date_expiration' => '2027-09-01', 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Formation continue'],
            ['id' => 'f5', 'intervenant' => 'Marie Leclerc', 'initials' => 'ML', 'intitule' => 'PSC1 — Premiers secours', 'organisme' => 'Croix-Rouge', 'date_obtention' => '2023-09-15', 'date_expiration' => '2025-09-15', 'statut' => 'expiree', 'statut_label' => 'Expirée', 'type' => 'Certification'],
            ['id' => 'f6', 'intervenant' => 'Luc Moreau', 'initials' => 'LM', 'intitule' => 'Gestes et postures — Manutention', 'organisme' => 'PRAP', 'date_obtention' => '2025-01-10', 'date_expiration' => '2027-01-10', 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Certification'],
            ['id' => 'f7', 'intervenant' => 'Marie Leclerc', 'initials' => 'ML', 'intitule' => 'Bientraitance et prévention maltraitance', 'organisme' => 'ANESM', 'date_obtention' => '2025-06-20', 'date_expiration' => '2028-06-20', 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Formation continue'],
            ['id' => 'f8', 'intervenant' => 'Luc Moreau', 'initials' => 'LM', 'intitule' => 'Aide à la prise médicamenteuse', 'organisme' => 'ARS IDF', 'date_obtention' => '2025-02-01', 'date_expiration' => '2026-08-01', 'statut' => 'expire_bientot', 'statut_label' => 'Expire bientôt', 'type' => 'Habilitation'],
        ];
    }
}
