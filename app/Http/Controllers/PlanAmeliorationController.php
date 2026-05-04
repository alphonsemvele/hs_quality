<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M6 — Plans d'Amélioration Continue (PAC).
 */
class PlanAmeliorationController extends Controller
{
    public function index(): Response
    {
        $demo = config('app.env') === 'local';

        return Inertia::render('dashboard/plans-amelioration/index', [
            'plans' => $demo ? $this->demoPlans() : [],
            'stats' => $demo
                ? ['total' => 3, 'en_cours' => 2, 'termines' => 1, 'taux_completion' => 58]
                : ['total' => 0, 'en_cours' => 0, 'termines' => 0, 'taux_completion' => null],
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
        $plan = collect($this->demoPlans())->firstWhere('id', $id);

        return Inertia::render('dashboard/plans-amelioration/show', [
            'plan' => $plan,
        ]);
    }

    public function update(string $id): RedirectResponse
    {
        return back()->with('info', 'Module Plans d\'amélioration en cours de développement.');
    }

    /** @return list<array<string, mixed>> */
    private function demoPlans(): array
    {
        return [
            [
                'id' => 'pac-001',
                'titre' => 'PAC — Traçabilité des transmissions',
                'source' => 'audit',
                'source_label' => 'Écart d\'audit',
                'constat' => "Constat lors de l'audit HAS 2026 : les transmissions orales entre intervenants ne sont pas systématiquement tracées dans le cahier numérique, ce qui crée des ruptures dans la continuité de l'accompagnement.",
                'statut' => 'en_cours',
                'statut_label' => 'En cours',
                'responsable' => 'Claire Bernard',
                'echeance' => '2026-06-30',
                'progression' => 66,
                'actions' => [
                    ['id' => 1, 'description' => 'Rédiger la procédure de transmission numérique obligatoire', 'responsable' => 'Claire Bernard', 'echeance' => '2026-04-15', 'statut' => 'done', 'realise_at' => '2026-04-12'],
                    ['id' => 2, 'description' => 'Former les 12 intervenants à l\'outil de transmission', 'responsable' => 'Thomas Dupont', 'echeance' => '2026-05-15', 'statut' => 'done', 'realise_at' => '2026-05-10'],
                    ['id' => 3, 'description' => 'Audit de conformité à 1 mois post-déploiement', 'responsable' => 'Claire Bernard', 'echeance' => '2026-06-30', 'statut' => 'en_cours', 'realise_at' => null],
                ],
            ],
            [
                'id' => 'pac-002',
                'titre' => 'PAC — Prévention des chutes à domicile',
                'source' => 'incident',
                'source_label' => 'Incident / EI',
                'constat' => 'Série de 3 incidents de chute en Q1 2026 impliquant des bénéficiaires GIR 2-3. Analyse racine : absence de check-list systématique d\'évaluation des risques domicile.',
                'statut' => 'en_cours',
                'statut_label' => 'En cours',
                'responsable' => 'Thomas Dupont',
                'echeance' => '2026-07-31',
                'progression' => 40,
                'actions' => [
                    ['id' => 4, 'description' => 'Créer la check-list d\'évaluation des risques domicile', 'responsable' => 'Claire Bernard', 'echeance' => '2026-04-30', 'statut' => 'done', 'realise_at' => '2026-04-28'],
                    ['id' => 5, 'description' => 'Réévaluer les 8 domiciles des bénéficiaires GIR 1-3', 'responsable' => 'Marie Leclerc', 'echeance' => '2026-06-15', 'statut' => 'en_cours', 'realise_at' => null],
                    ['id' => 6, 'description' => 'Installer les équipements de prévention identifiés', 'responsable' => 'Thomas Dupont', 'echeance' => '2026-07-15', 'statut' => 'planifiee', 'realise_at' => null],
                    ['id' => 7, 'description' => 'Former les intervenants aux gestes de prévention', 'responsable' => 'Anne Petit', 'echeance' => '2026-07-31', 'statut' => 'planifiee', 'realise_at' => null],
                    ['id' => 8, 'description' => 'Mesurer l\'efficacité à 3 mois (taux d\'incidents chute)', 'responsable' => 'Claire Bernard', 'echeance' => '2026-10-31', 'statut' => 'planifiee', 'realise_at' => null],
                ],
            ],
            [
                'id' => 'pac-003',
                'titre' => 'PAC — Protocole médicamenteux',
                'source' => 'audit',
                'source_label' => 'Écart d\'audit',
                'constat' => 'Protocole de gestion médicamenteuse non mis à jour depuis 14 mois (audit HAS 2026, écart majeur).',
                'statut' => 'termine',
                'statut_label' => 'Terminé',
                'responsable' => 'Claire Bernard',
                'echeance' => '2026-04-30',
                'progression' => 100,
                'actions' => [
                    ['id' => 9, 'description' => 'Réviser le protocole avec le médecin coordonnateur', 'responsable' => 'Claire Bernard', 'echeance' => '2026-03-31', 'statut' => 'done', 'realise_at' => '2026-03-28'],
                    ['id' => 10, 'description' => 'Diffuser le protocole révisé et recueillir les accusés de réception', 'responsable' => 'Thomas Dupont', 'echeance' => '2026-04-15', 'statut' => 'done', 'realise_at' => '2026-04-14'],
                    ['id' => 11, 'description' => 'Contrôle terrain de la bonne application', 'responsable' => 'Claire Bernard', 'echeance' => '2026-04-30', 'statut' => 'done', 'realise_at' => '2026-04-29'],
                ],
            ],
        ];
    }
}
