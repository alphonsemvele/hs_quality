<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| QVCT — French strings
|--------------------------------------------------------------------------
| Phase 2 / M3 (PHASE2_PROGRESS.md row M3.20). UI-facing labels and
| validation overrides for the QVCT module. Form requests already inline
| French messages where one form needs them; this file is for strings
| reused across multiple controllers, the dashboard tile, and any future
| email/notification templates.
*/

return [
    // Module label
    'module' => 'Baromètre QVCT',
    'short_module' => 'QVCT',

    // Lifecycle labels
    'campaign' => [
        'launched' => 'Campagne lancée.',
        'closed' => 'Campagne clôturée — détection des signaux faibles déclenchée.',
        'cannot_launch_archived' => 'Impossible de lancer une campagne depuis un questionnaire archivé.',
        'cannot_respond_closed' => 'Impossible de soumettre une réponse à une campagne clôturée ou en brouillon.',
    ],

    // Anonymity reminder, surfaced on the response form to reassure intervenants.
    'response' => [
        'anonymous_notice' => 'Vos réponses sont strictement anonymes. Aucune information permettant '
            .'de vous identifier (identifiant, IP, appareil) n\'est enregistrée.',
        'submitted' => 'Réponse enregistrée. Merci.',
    ],

    // Weak-signal triage labels (RH dashboard).
    'weak_signal' => [
        'baisse_morale' => 'Baisse de morale',
        'surcharge' => 'Surcharge de travail',
        'conflit_relationnel' => 'Conflit relationnel',
        'isolement_professionnel' => 'Isolement professionnel',
        'acknowledged_by' => 'Pris en compte par :name le :date',
        'severity_label' => [
            1 => 'Léger',
            2 => 'Modéré',
            3 => 'Critique',
        ],
    ],

    // Cadence labels (mirror QvctFrequency::label() for UI use).
    'frequency' => [
        'weekly' => 'Hebdomadaire',
        'monthly' => 'Mensuelle',
        'quarterly' => 'Trimestrielle',
    ],
];
