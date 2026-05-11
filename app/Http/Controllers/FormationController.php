<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M5 — Compétences & Formation.
 *
 * Inertia hub aggregating plans, sessions, habilitations and certifications.
 * Dedicated API controllers (`Api\V1\TrainingPlanController`,
 * `TrainingAttendanceController`, `HabilitationController`,
 * `CertificationController`) keep the write paths for mobile parity.
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
            'plans' => $demo ? $this->demoTrainingPlans() : [],
            'sessions' => $demo ? $this->demoSessions() : [],
            'expiringAlerts' => $demo ? $this->demoExpiringAlerts() : [],
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
            ['id' => 'f1', 'intervenant' => 'Marie Leclerc', 'initials' => 'ML', 'intitule' => 'Aide à la toilette et soins d\'hygiène', 'organisme' => 'INRS', 'date_obtention' => '2025-03-15', 'date_expiration' => '2027-03-15', 'days_to_expiry' => 670, 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Habilitation'],
            ['id' => 'f2', 'intervenant' => 'Marie Leclerc', 'initials' => 'ML', 'intitule' => 'Gestes et postures — Manutention', 'organisme' => 'PRAP', 'date_obtention' => '2024-11-20', 'date_expiration' => '2026-11-20', 'days_to_expiry' => 193, 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Certification'],
            ['id' => 'f3', 'intervenant' => 'Luc Moreau', 'initials' => 'LM', 'intitule' => 'PSC1 — Premiers secours', 'organisme' => 'Croix-Rouge', 'date_obtention' => '2024-06-10', 'date_expiration' => '2026-06-10', 'days_to_expiry' => 30, 'statut' => 'expire_bientot', 'statut_label' => 'Expire bientôt', 'type' => 'Certification'],
            ['id' => 'f4', 'intervenant' => 'Luc Moreau', 'initials' => 'LM', 'intitule' => 'Accompagnement Alzheimer', 'organisme' => 'France Alzheimer', 'date_obtention' => '2025-09-01', 'date_expiration' => '2027-09-01', 'days_to_expiry' => 843, 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Formation continue'],
            ['id' => 'f5', 'intervenant' => 'Marie Leclerc', 'initials' => 'ML', 'intitule' => 'PSC1 — Premiers secours', 'organisme' => 'Croix-Rouge', 'date_obtention' => '2023-09-15', 'date_expiration' => '2025-09-15', 'days_to_expiry' => -238, 'statut' => 'expiree', 'statut_label' => 'Expirée', 'type' => 'Certification'],
            ['id' => 'f6', 'intervenant' => 'Luc Moreau', 'initials' => 'LM', 'intitule' => 'Gestes et postures — Manutention', 'organisme' => 'PRAP', 'date_obtention' => '2025-01-10', 'date_expiration' => '2027-01-10', 'days_to_expiry' => 609, 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Certification'],
            ['id' => 'f7', 'intervenant' => 'Marie Leclerc', 'initials' => 'ML', 'intitule' => 'Bientraitance et prévention maltraitance', 'organisme' => 'ANESM', 'date_obtention' => '2025-06-20', 'date_expiration' => '2028-06-20', 'days_to_expiry' => 1135, 'statut' => 'valide', 'statut_label' => 'Valide', 'type' => 'Formation continue'],
            ['id' => 'f8', 'intervenant' => 'Luc Moreau', 'initials' => 'LM', 'intitule' => 'Aide à la prise médicamenteuse', 'organisme' => 'ARS IDF', 'date_obtention' => '2025-02-01', 'date_expiration' => '2026-08-01', 'days_to_expiry' => 82, 'statut' => 'expire_bientot', 'statut_label' => 'Expire bientôt', 'type' => 'Habilitation'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoTrainingPlans(): array
    {
        return [
            [
                'id' => 'tp-001',
                'year' => 2026,
                'theme' => 'Bientraitance et prévention RPS',
                'target_audience' => 'Tous intervenants',
                'status' => 'published',
                'status_label' => 'Publié',
                'sessions_total' => 4,
                'sessions_done' => 2,
                'participants_total' => 24,
                'participants_done' => 14,
            ],
            [
                'id' => 'tp-002',
                'year' => 2026,
                'theme' => 'Renouvellement PSC1',
                'target_audience' => 'Intervenants dont certif < 6 mois',
                'status' => 'published',
                'status_label' => 'Publié',
                'sessions_total' => 3,
                'sessions_done' => 1,
                'participants_total' => 12,
                'participants_done' => 4,
            ],
            [
                'id' => 'tp-003',
                'year' => 2026,
                'theme' => 'Accompagnement fin de vie',
                'target_audience' => 'Volontaires + référent qualité',
                'status' => 'draft',
                'status_label' => 'Brouillon',
                'sessions_total' => 0,
                'sessions_done' => 0,
                'participants_total' => 0,
                'participants_done' => 0,
            ],
            [
                'id' => 'tp-004',
                'year' => 2025,
                'theme' => 'Hygiène et soins de base',
                'target_audience' => 'Tous intervenants',
                'status' => 'archived',
                'status_label' => 'Archivé',
                'sessions_total' => 4,
                'sessions_done' => 4,
                'participants_total' => 22,
                'participants_done' => 22,
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoSessions(): array
    {
        return [
            ['id' => 's-001', 'date' => '2026-05-14', 'time' => '09:00', 'title' => 'PSC1 — Session de rappel', 'location' => 'Croix-Rouge Paris 11e', 'capacity' => 8, 'registered' => 6, 'status' => 'scheduled'],
            ['id' => 's-002', 'date' => '2026-05-22', 'time' => '14:00', 'title' => 'Bientraitance — Module 1', 'location' => 'Visioconférence', 'capacity' => 15, 'registered' => 12, 'status' => 'scheduled'],
            ['id' => 's-003', 'date' => '2026-05-28', 'time' => '10:00', 'title' => 'Manutention — Atelier pratique', 'location' => 'INRS', 'capacity' => 6, 'registered' => 6, 'status' => 'full'],
            ['id' => 's-004', 'date' => '2026-06-04', 'time' => '09:00', 'title' => 'Bientraitance — Module 2', 'location' => 'Visioconférence', 'capacity' => 15, 'registered' => 8, 'status' => 'scheduled'],
            ['id' => 's-005', 'date' => '2026-06-18', 'time' => '14:00', 'title' => 'PSC1 — Session de rappel', 'location' => 'Croix-Rouge Paris 11e', 'capacity' => 8, 'registered' => 3, 'status' => 'scheduled'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoExpiringAlerts(): array
    {
        return [
            ['id' => 'f3', 'intervenant' => 'Luc Moreau', 'intitule' => 'PSC1 — Premiers secours', 'date_expiration' => '2026-06-10', 'days_to_expiry' => 30, 'severity' => 'urgent'],
            ['id' => 'f8', 'intervenant' => 'Luc Moreau', 'intitule' => 'Aide à la prise médicamenteuse', 'date_expiration' => '2026-08-01', 'days_to_expiry' => 82, 'severity' => 'warning'],
            ['id' => 'f5', 'intervenant' => 'Marie Leclerc', 'intitule' => 'PSC1 — Premiers secours', 'date_expiration' => '2025-09-15', 'days_to_expiry' => -238, 'severity' => 'expired'],
        ];
    }
}
