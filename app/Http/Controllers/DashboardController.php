<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // ── Stats ─────────────────────────────────────────────────────────────
        $stats = [
            'interventions_ce_mois' => 1842,
            'interventions_en_cours' => 23,
            'incidents_declares' => 14,
            'incidents_en_cours' => 3,
            'score_conformite' => 78,
            'taux_completion_pac' => 62,
            'score_qvct_moyen' => 6.8,
            'formations_expirant_bientot' => 5,
            'intervenants_actifs' => 87,
            'structures_actives' => 12,
        ];

        // ── Incidents récents ─────────────────────────────────────────────────
        $incidents_recents = [
            [
                'id' => 1,
                'initials' => 'ME',
                'declarant' => 'Marie Essomba',
                'categorie' => 'Chute',
                'gravite' => 'significatif',
                'statut' => 'en_analyse',
                'structure' => 'SAAD Horizon Douala',
                'depuis' => '2h',
            ],
            [
                'id' => 2,
                'initials' => 'JK',
                'declarant' => 'Jean Koffi',
                'categorie' => 'Erreur médicamenteuse',
                'gravite' => 'grave',
                'statut' => 'plan_actions',
                'structure' => 'SSIAD Centre Yaoundé',
                'depuis' => '5h',
            ],
            [
                'id' => 3,
                'initials' => 'AF',
                'declarant' => 'Amina Fofana',
                'categorie' => 'Agression',
                'gravite' => 'critique',
                'statut' => 'declare',
                'structure' => 'SPASAD Nord',
                'depuis' => '18 min',
            ],
            [
                'id' => 4,
                'initials' => 'PB',
                'declarant' => 'Alphonse loic.',
                'categorie' => 'Maltraitance suspectée',
                'gravite' => 'grave',
                'statut' => 'en_analyse',
                'structure' => 'SAAD Sud Littoral',
                'depuis' => '1j',
            ],
            [
                'id' => 5,
                'initials' => 'FN',
                'declarant' => 'Fatima Ndiaye',
                'categorie' => 'Chute',
                'gravite' => 'mineur',
                'statut' => 'clos',
                'structure' => 'SAAD Horizon Douala',
                'depuis' => '3j',
            ],
            [
                'id' => 6,
                'initials' => 'CT',
                'declarant' => 'Clément Touré',
                'categorie' => 'Accident de travail',
                'gravite' => 'significatif',
                'statut' => 'plan_actions',
                'structure' => 'SSIAD Centre Yaoundé',
                'depuis' => '2j',
            ],
        ];

        // ── Alertes QVCT ──────────────────────────────────────────────────────
        $alertes_qvct = [
            [
                'id' => 1,
                'intervenant' => 'Sophie Ateba',
                'structure' => 'SAAD Horizon Douala',
                'score' => 2.8,
                'signal' => 'Score bas 2 périodes consécutives',
                'depuis' => '7 jours',
            ],
            [
                'id' => 2,
                'intervenant' => 'Bruno Ngono',
                'structure' => 'SSIAD Centre Yaoundé',
                'score' => 3.1,
                'signal' => 'Surcharge de travail détectée',
                'depuis' => '3 jours',
            ],
            [
                'id' => 3,
                'intervenant' => 'Pascaline Eko',
                'structure' => 'SPASAD Nord',
                'score' => 3.4,
                'signal' => 'Isolement professionnel signalé',
                'depuis' => '5 jours',
            ],
        ];

        // ── Audits récents ────────────────────────────────────────────────────
        $audits_recents = [
            [
                'id' => 1,
                'structure' => 'SAAD Horizon Douala',
                'type_grille' => 'HAS Évaluation externe',
                'score' => 84,
                'statut' => 'finalise',
                'date' => '05/04/2026',
            ],
            [
                'id' => 2,
                'structure' => 'SSIAD Centre Yaoundé',
                'type_grille' => 'AFNOR NF X50-056',
                'score' => 71,
                'statut' => 'en_cours',
                'date' => '08/04/2026',
            ],
            [
                'id' => 3,
                'structure' => 'SPASAD Nord',
                'type_grille' => 'ISO 9001',
                'score' => 0,
                'statut' => 'planifie',
                'date' => '15/04/2026',
            ],
            [
                'id' => 4,
                'structure' => 'SAAD Sud Littoral',
                'type_grille' => 'Caphandeo',
                'score' => 91,
                'statut' => 'finalise',
                'date' => '02/04/2026',
            ],
        ];

        return Inertia::render('dashboard/index', [
            'stats' => $stats,
            'incidents_recents' => $incidents_recents,
            'alertes_qvct' => $alertes_qvct,
            'audits_recents' => $audits_recents,
        ]);
    }
}
