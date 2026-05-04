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
 * is not yet implemented. Controllers pass demo data in local environment
 * and empty data in production.
 */
class AuditController extends Controller
{
    public function index(): Response
    {
        $demo = $this->isDemoMode();

        return Inertia::render('dashboard/audits/index', [
            'audits' => $demo ? $this->demoAudits() : [],
            'stats' => $demo
                ? ['total' => 4, 'en_cours' => 1, 'termines' => 2, 'score_moyen' => 78]
                : ['total' => 0, 'en_cours' => 0, 'termines' => 0, 'score_moyen' => null],
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
        return back()->with('info', 'Module Audits en cours de développement.');
    }

    public function show(string $id): Response
    {
        $demo = $this->isDemoMode();
        $audit = $demo ? collect($this->demoAudits())->firstWhere('id', $id) : null;

        return Inertia::render('dashboard/audits/show', [
            'audit' => $audit,
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

    private function isDemoMode(): bool
    {
        return config('app.env') === 'local';
    }

    /** @return list<array<string, mixed>> */
    private function demoAudits(): array
    {
        return [
            [
                'id' => 'audit-001',
                'titre' => 'Audit HAS — Évaluation externe annuelle',
                'referentiel' => 'has',
                'referentiel_label' => 'HAS — Évaluation externe',
                'date_audit' => '2026-03-15',
                'statut' => 'termine',
                'statut_label' => 'Terminé',
                'score' => 82,
                'auditeur' => 'Dr. Lefèvre (cabinet AQS)',
                'nb_ecarts' => 3,
                'description' => "Évaluation externe annuelle couvrant les 5 domaines du référentiel HAS :\n- Droits des usagers\n- Personnalisation de l'accompagnement\n- Organisation interne\n- Prévention des risques\n- Amélioration continue",
                'ecarts' => [
                    ['id' => 1, 'critere' => 'Traçabilité des transmissions', 'constat' => 'Transmissions orales non systématiquement tracées dans le cahier numérique', 'gravite' => 'majeur', 'action_corrective' => 'Mise en place du cahier de transmission numérique obligatoire'],
                    ['id' => 2, 'critere' => 'Plan de formation', 'constat' => 'Absence de plan de formation formalisé pour 2026', 'gravite' => 'mineur', 'action_corrective' => 'Élaboration du plan avant fin Q2'],
                    ['id' => 3, 'critere' => 'Protocole médicamenteux', 'constat' => 'Protocole non mis à jour depuis 14 mois', 'gravite' => 'majeur', 'action_corrective' => null],
                ],
            ],
            [
                'id' => 'audit-002',
                'titre' => 'Audit interne — Prévention des chutes',
                'referentiel' => 'interne',
                'referentiel_label' => 'Audit interne personnalisé',
                'date_audit' => '2026-04-02',
                'statut' => 'en_cours',
                'statut_label' => 'En cours',
                'score' => null,
                'auditeur' => 'Claire Bernard (Réf. Qualité)',
                'nb_ecarts' => 0,
                'description' => 'Audit ciblé sur les pratiques de prévention des chutes à domicile suite à la série d\'incidents déclarés en Q1.',
                'ecarts' => [],
            ],
            [
                'id' => 'audit-003',
                'titre' => 'Audit AFNOR NF X50-056 — Certification',
                'referentiel' => 'afnor',
                'referentiel_label' => 'AFNOR NF X50-056',
                'date_audit' => '2026-01-20',
                'statut' => 'termine',
                'statut_label' => 'Terminé',
                'score' => 74,
                'auditeur' => 'M. Garnier (AFNOR Certification)',
                'nb_ecarts' => 5,
                'description' => null,
                'ecarts' => [
                    ['id' => 4, 'critere' => 'Gestion documentaire', 'constat' => 'Documents qualité non versionnés', 'gravite' => 'mineur', 'action_corrective' => 'GED mise en place'],
                    ['id' => 5, 'critere' => 'Évaluation à domicile', 'constat' => 'Grille d\'évaluation incomplète', 'gravite' => 'majeur', 'action_corrective' => null],
                    ['id' => 6, 'critere' => 'Suivi des réclamations', 'constat' => 'Délai moyen de réponse > 15 jours', 'gravite' => 'majeur', 'action_corrective' => 'Objectif ramené à 7 jours ouvrés'],
                    ['id' => 7, 'critere' => 'Formation continue', 'constat' => '2 intervenants sans habilitation à jour', 'gravite' => 'critique', 'action_corrective' => 'Sessions de rattrapage planifiées'],
                    ['id' => 8, 'critere' => 'Protocole urgence', 'constat' => 'Numéros d\'urgence non affichés dans 3 domiciles', 'gravite' => 'mineur', 'action_corrective' => 'Affichage systématique lors de la prochaine visite'],
                ],
            ],
            [
                'id' => 'audit-004',
                'titre' => 'Audit ISO 9001 — Pré-audit de certification',
                'referentiel' => 'iso9001',
                'referentiel_label' => 'ISO 9001:2015',
                'date_audit' => '2026-06-10',
                'statut' => 'planifie',
                'statut_label' => 'Planifié',
                'score' => null,
                'auditeur' => null,
                'nb_ecarts' => 0,
                'description' => 'Pré-audit en vue de la certification ISO 9001:2015 prévue pour le second semestre.',
                'ecarts' => [],
            ],
        ];
    }
}
