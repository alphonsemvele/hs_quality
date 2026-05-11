<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M3 — QVCT (Qualité de Vie et Conditions de Travail).
 *
 * Hub renderer + read-only questionnaire surface. The full M3 wiring
 * (campaign launch, weak-signal acknowledgement, indicator publishing)
 * lives in dedicated controllers / API endpoints — this file is kept
 * deliberately small.
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
            'trend' => $demo ? $this->demoTrend() : [],
            'weak_signals' => $demo ? $this->demoWeakSignals() : [],
            'team_breakdown' => $demo ? $this->demoTeamBreakdown() : [],
        ]);
    }

    public function questionnaire(): Response
    {
        $demo = config('app.env') === 'local';

        return Inertia::render('dashboard/qvct/questionnaire', [
            'campagne' => $demo
                ? [
                    'id' => 'camp-current',
                    'titre' => 'Baromètre QVCT — Mai 2026',
                    'date_fin' => '2026-05-31',
                    'description' => 'Vos réponses nous aident à ajuster nos plans d\'action et à mieux soutenir les équipes.',
                ]
                : null,
            'questions' => $demo ? $this->demoQuestions() : [],
            'threshold' => 5,
        ]);
    }

    public function store(): RedirectResponse
    {
        return back()->with('info', 'Module QVCT en cours de développement.');
    }

    public function weakSignals(): Response
    {
        $demo = config('app.env') === 'local';

        return Inertia::render('dashboard/qvct/weak-signals/index', [
            'signals' => $demo ? $this->demoWeakSignalsFull() : [],
            'stats' => $demo
                ? ['total' => 7, 'unacknowledged' => 4, 'critical' => 2, 'this_week' => 3]
                : ['total' => 0, 'unacknowledged' => 0, 'critical' => 0, 'this_week' => 0],
        ]);
    }

    public function acknowledgeWeakSignal(string $id): RedirectResponse
    {
        return back()->with('success', "Signal {$id} pris en compte (demo).");
    }

    public function indicators(): Response
    {
        $demo = config('app.env') === 'local';

        return Inertia::render('dashboard/qvct/indicators/index', [
            'dimensions' => $demo ? $this->demoDimensions() : [],
            'matrix' => $demo ? $this->demoHeatmap() : ['teams' => [], 'cells' => []],
            'trend' => $demo ? $this->demoTrend() : [],
        ]);
    }

    public function actionPlans(): Response
    {
        $demo = config('app.env') === 'local';

        return Inertia::render('dashboard/qvct/action-plans/index', [
            'plans' => $demo ? $this->demoActionPlans() : [],
            'stats' => $demo
                ? ['total' => 4, 'open' => 2, 'closed' => 2, 'items_in_progress' => 6]
                : ['total' => 0, 'open' => 0, 'closed' => 0, 'items_in_progress' => 0],
        ]);
    }

    public function journal(): Response
    {
        $demo = config('app.env') === 'local';

        return Inertia::render('dashboard/qvct/journal/index', [
            'entries' => $demo ? $this->demoJournal() : [],
            'shared_count' => $demo ? 2 : 0,
        ]);
    }

    public function storeJournalEntry(): RedirectResponse
    {
        return back()->with('success', 'Entrée enregistrée (demo).');
    }

    public function exchanges(): Response
    {
        $demo = config('app.env') === 'local';

        return Inertia::render('dashboard/qvct/exchanges/index', [
            'inbox' => $demo ? $this->demoExchangesInbox() : [],
            'outbox' => $demo ? $this->demoExchangesOutbox() : [],
        ]);
    }

    public function storeExchange(): RedirectResponse
    {
        return back()->with('success', 'Demande d\'échange envoyée (demo).');
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

    /** @return list<array{label:string,value:float}> */
    private function demoTrend(): array
    {
        $labels = ['Oct', 'Nov', 'Déc', 'Jan', 'Fév', 'Mar', 'Avr'];
        $values = [6.4, 6.5, 6.3, 7.2, 7.0, 6.9, 6.8];

        return array_map(
            fn ($label, $value) => ['label' => $label, 'value' => $value],
            $labels,
            $values,
        );
    }

    /** @return list<array<string, mixed>> */
    private function demoWeakSignals(): array
    {
        return [
            [
                'id' => 'ws-001',
                'type' => 'burnout_risk',
                'team' => 'Secteur Nord — soirée',
                'score' => 7.2,
                'detected_at' => 'Détecté il y a 2 jours',
                'acknowledged' => false,
            ],
            [
                'id' => 'ws-002',
                'type' => 'rps_cluster',
                'team' => 'Secteur Sud — week-end',
                'score' => 6.5,
                'detected_at' => 'Détecté il y a 5 jours',
                'acknowledged' => false,
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoTeamBreakdown(): array
    {
        return [
            ['team' => 'Secteur Nord', 'score' => 5.8, 'response_rate' => 70, 'tone' => 'warning'],
            ['team' => 'Secteur Sud', 'score' => 6.2, 'response_rate' => 75, 'tone' => 'warning'],
            ['team' => 'Secteur Est', 'score' => 7.4, 'response_rate' => 82, 'tone' => 'sage'],
            ['team' => 'Secteur Ouest', 'score' => 7.8, 'response_rate' => 88, 'tone' => 'sage'],
            ['team' => 'Coordination', 'score' => 6.9, 'response_rate' => 100, 'tone' => 'brand'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoQuestions(): array
    {
        return [
            [
                'id' => 'q-mood',
                'type' => 'mood',
                'label' => 'Comment vous sentez-vous globalement cette semaine ?',
                'help' => 'Sélectionnez l\'option qui correspond le mieux à votre ressenti général.',
                'required' => true,
            ],
            [
                'id' => 'q-charge',
                'type' => 'likert',
                'label' => 'Ma charge de travail est-elle raisonnable ?',
                'min_label' => 'Pas du tout',
                'max_label' => 'Totalement',
                'required' => true,
            ],
            [
                'id' => 'q-soutien',
                'type' => 'likert',
                'label' => 'Je me sens soutenu·e par mes collègues et ma hiérarchie',
                'min_label' => 'Pas du tout',
                'max_label' => 'Totalement',
                'required' => true,
            ],
            [
                'id' => 'q-sens',
                'type' => 'likert',
                'label' => 'Mon travail a du sens et de l\'utilité',
                'min_label' => 'Pas du tout',
                'max_label' => 'Totalement',
                'required' => true,
            ],
            [
                'id' => 'q-recup',
                'type' => 'likert',
                'label' => 'Je récupère bien entre deux journées de travail',
                'min_label' => 'Pas du tout',
                'max_label' => 'Totalement',
                'required' => true,
            ],
            [
                'id' => 'q-libre',
                'type' => 'open',
                'label' => 'Avez-vous un commentaire libre ? (optionnel)',
                'help' => 'Ce champ reste anonyme. Aucune donnée identifiante ne sera conservée.',
                'required' => false,
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoWeakSignalsFull(): array
    {
        return [
            [
                'id' => 'ws-001',
                'type' => 'burnout_risk',
                'team' => 'Secteur Nord — soirée',
                'score' => 7.2,
                'detected_at' => 'Détecté il y a 2 jours',
                'severity' => 'critical',
                'respondents' => 6,
                'acknowledged' => false,
                'campaign' => 'Baromètre QVCT — Avril 2026',
            ],
            [
                'id' => 'ws-002',
                'type' => 'rps_cluster',
                'team' => 'Secteur Sud — week-end',
                'score' => 6.5,
                'detected_at' => 'Détecté il y a 5 jours',
                'severity' => 'critical',
                'respondents' => 4,
                'acknowledged' => false,
                'campaign' => 'Baromètre QVCT — Avril 2026',
            ],
            [
                'id' => 'ws-003',
                'type' => 'autonomy_loss',
                'team' => 'Coordination',
                'score' => 5.4,
                'detected_at' => 'Détecté il y a 7 jours',
                'severity' => 'attention',
                'respondents' => 3,
                'acknowledged' => false,
                'campaign' => 'Baromètre QVCT — Avril 2026',
            ],
            [
                'id' => 'ws-004',
                'type' => 'engagement_drop',
                'team' => 'Secteur Est',
                'score' => 4.8,
                'detected_at' => 'Détecté il y a 12 jours',
                'severity' => 'attention',
                'respondents' => 5,
                'acknowledged' => false,
                'campaign' => 'Baromètre QVCT — Avril 2026',
            ],
            [
                'id' => 'ws-005',
                'type' => 'burnout_risk',
                'team' => 'Secteur Nord',
                'score' => 6.1,
                'detected_at' => 'Détecté il y a 28 jours',
                'severity' => 'attention',
                'respondents' => 4,
                'acknowledged' => true,
                'campaign' => 'Baromètre QVCT — Janvier 2026',
            ],
            [
                'id' => 'ws-006',
                'type' => 'rps_cluster',
                'team' => 'Secteur Sud',
                'score' => 5.9,
                'detected_at' => 'Détecté il y a 32 jours',
                'severity' => 'attention',
                'respondents' => 6,
                'acknowledged' => true,
                'campaign' => 'Baromètre QVCT — Janvier 2026',
            ],
            [
                'id' => 'ws-007',
                'type' => 'other',
                'team' => 'Secteur Ouest',
                'score' => 4.2,
                'detected_at' => 'Détecté il y a 45 jours',
                'severity' => 'attention',
                'respondents' => 3,
                'acknowledged' => true,
                'campaign' => 'Baromètre QVCT — Janvier 2026',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoDimensions(): array
    {
        return [
            ['key' => 'charge', 'label' => 'Charge de travail', 'description' => 'Volume et intensité du travail.'],
            ['key' => 'soutien', 'label' => 'Soutien social', 'description' => 'Collègues et hiérarchie.'],
            ['key' => 'sens', 'label' => 'Sens du travail', 'description' => 'Utilité et reconnaissance.'],
            ['key' => 'recup', 'label' => 'Récupération', 'description' => 'Repos et coupures.'],
            ['key' => 'autonomie', 'label' => 'Autonomie', 'description' => 'Marge de manœuvre.'],
        ];
    }

    /** @return array<string, mixed> */
    private function demoHeatmap(): array
    {
        $teams = ['Secteur Nord', 'Secteur Sud', 'Secteur Est', 'Secteur Ouest', 'Coordination'];
        $dimensions = ['charge', 'soutien', 'sens', 'recup', 'autonomie'];

        $scores = [
            'Secteur Nord' => ['charge' => 4.2, 'soutien' => 6.5, 'sens' => 7.8, 'recup' => 5.1, 'autonomie' => 6.0],
            'Secteur Sud' => ['charge' => 5.0, 'soutien' => 5.8, 'sens' => 7.2, 'recup' => 5.4, 'autonomie' => 6.4],
            'Secteur Est' => ['charge' => 6.8, 'soutien' => 7.5, 'sens' => 8.2, 'recup' => 7.0, 'autonomie' => 7.4],
            'Secteur Ouest' => ['charge' => 7.2, 'soutien' => 8.0, 'sens' => 8.5, 'recup' => 7.4, 'autonomie' => 7.8],
            'Coordination' => ['charge' => 5.5, 'soutien' => 7.0, 'sens' => 8.6, 'recup' => 6.2, 'autonomie' => 7.0],
        ];

        $cells = [];
        foreach ($teams as $team) {
            foreach ($dimensions as $dim) {
                $cells[] = [
                    'team' => $team,
                    'dimension' => $dim,
                    'score' => $scores[$team][$dim],
                ];
            }
        }

        return ['teams' => $teams, 'cells' => $cells];
    }

    /** @return list<array<string, mixed>> */
    private function demoActionPlans(): array
    {
        return [
            [
                'id' => 'ap-001',
                'title' => 'Réduire la charge de soirée — Secteur Nord',
                'status' => 'published',
                'status_label' => 'Publié',
                'published_at' => '2026-04-20',
                'closed_at' => null,
                'owner' => 'Claire Dupont (RH)',
                'items_total' => 4,
                'items_done' => 2,
                'items_in_progress' => 1,
                'impact_target' => 'Score charge +1.5 pt',
                'tone' => 'sage',
            ],
            [
                'id' => 'ap-002',
                'title' => 'Plan soutien week-end — Secteur Sud',
                'status' => 'published',
                'status_label' => 'Publié',
                'published_at' => '2026-04-15',
                'closed_at' => null,
                'owner' => 'Pierre Martin (Dirigeant)',
                'items_total' => 3,
                'items_done' => 1,
                'items_in_progress' => 1,
                'impact_target' => 'Réduire signal RPS week-end',
                'tone' => 'brand',
            ],
            [
                'id' => 'ap-003',
                'title' => 'Cycle d\'écoute — Coordination',
                'status' => 'draft',
                'status_label' => 'Brouillon',
                'published_at' => null,
                'closed_at' => null,
                'owner' => 'Claire Dupont (RH)',
                'items_total' => 2,
                'items_done' => 0,
                'items_in_progress' => 0,
                'impact_target' => 'Mise en place entretiens 1:1 mensuels',
                'tone' => 'warning',
            ],
            [
                'id' => 'ap-004',
                'title' => 'Formation gestes & posture — Tous secteurs',
                'status' => 'closed',
                'status_label' => 'Clos',
                'published_at' => '2026-02-01',
                'closed_at' => '2026-04-10',
                'owner' => 'Claire Dupont (RH)',
                'items_total' => 5,
                'items_done' => 5,
                'items_in_progress' => 0,
                'impact_target' => '100 % des intervenants formés',
                'tone' => 'sage',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoJournal(): array
    {
        return [
            [
                'id' => 'je-001',
                'date' => '2026-05-10',
                'mood' => 4,
                'content' => 'Bonne semaine — bénéficiaires en forme, planning respecté. Un peu de fatigue jeudi mais bien récupéré.',
                'shared_with_rh' => false,
            ],
            [
                'id' => 'je-002',
                'date' => '2026-05-03',
                'mood' => 3,
                'content' => 'Charge importante cette semaine, 3 visites supplémentaires. Besoin de souffler.',
                'shared_with_rh' => true,
                'shared_at' => '2026-05-04',
            ],
            [
                'id' => 'je-003',
                'date' => '2026-04-26',
                'mood' => 5,
                'content' => 'Très belle semaine — j\'ai pu accompagner Mme L. à son rendez-vous médical, très reconnaissante.',
                'shared_with_rh' => false,
            ],
            [
                'id' => 'je-004',
                'date' => '2026-04-19',
                'mood' => 2,
                'content' => 'Difficile — incident chez M. R., je ne sais pas si j\'ai bien réagi. Voir avec coordinateur.',
                'shared_with_rh' => true,
                'shared_at' => '2026-04-20',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoExchangesInbox(): array
    {
        return [
            [
                'id' => 'ex-001',
                'from' => 'Sophie Bernard (intervenante)',
                'subject' => 'Souhait d\'échange',
                'reason' => 'Charge de travail',
                'status' => 'requested',
                'status_label' => 'Nouvelle',
                'requested_at' => 'Il y a 3 heures',
                'scheduled_at' => null,
            ],
            [
                'id' => 'ex-002',
                'from' => 'Karim Benali (intervenant)',
                'subject' => 'Demande d\'écoute',
                'reason' => 'Conflit collègue',
                'status' => 'accepted',
                'status_label' => 'Acceptée — à planifier',
                'requested_at' => 'Il y a 1 jour',
                'scheduled_at' => null,
            ],
            [
                'id' => 'ex-003',
                'from' => 'Marie Lefèvre (intervenante)',
                'subject' => 'Point sur le planning',
                'reason' => 'Organisation',
                'status' => 'scheduled',
                'status_label' => 'Planifié',
                'requested_at' => 'Il y a 4 jours',
                'scheduled_at' => 'Vendredi 15 mai, 14h',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoExchangesOutbox(): array
    {
        return [
            [
                'id' => 'ex-mine-001',
                'to' => 'Claire Dupont (RH)',
                'subject' => 'Préparation entretien annuel',
                'status' => 'scheduled',
                'status_label' => 'Planifié',
                'requested_at' => 'Il y a 6 jours',
                'scheduled_at' => 'Lundi 18 mai, 10h',
            ],
        ];
    }
}
