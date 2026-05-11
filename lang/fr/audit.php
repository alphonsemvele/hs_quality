<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Audits + PAC — French strings
|--------------------------------------------------------------------------
| Phase 2 / M6 (PHASE2_PROGRESS.md row M6.25). UI-facing labels and
| validation messages reused across the audit + PAC controllers and the
| dashboard tile. Form-request inline messages remain for endpoint-
| specific overrides.
*/

return [
    // Module label
    'module' => 'Audits & Conformité',
    'short_module' => 'Audits',

    'grid' => [
        'source' => [
            'has' => 'HAS — Haute Autorité de Santé',
            'iso_9001' => 'ISO 9001',
            'afnor_x50_056' => 'AFNOR NF X50-056',
            'custom' => 'Grille personnalisée',
        ],
    ],

    'run' => [
        'started' => 'Évaluation démarrée.',
        'finalised' => 'Évaluation finalisée. Score figé.',
        'cannot_record_finalised' => 'Impossible d\'enregistrer une réponse sur une évaluation finalisée.',
        'item_must_belong_to_grid' => 'L\'item ne fait pas partie de la grille de cette évaluation.',
        'cannot_finalise_twice' => 'Cette évaluation est déjà finalisée.',
        'status_label' => [
            'draft' => 'Brouillon',
            'in_progress' => 'En cours',
            'finalised' => 'Finalisée',
        ],
    ],

    'pac' => [
        'generated' => 'PAC généré automatiquement à partir des écarts détectés.',
        'must_be_finalised' => 'Le PAC ne peut être généré qu\'à partir d\'une évaluation finalisée.',
        'closed' => 'PAC clôturé.',
        'status_label' => [
            'draft' => 'Brouillon',
            'active' => 'Actif',
            'closed' => 'Clôturé',
        ],
        'action_status_label' => [
            'pending' => 'À démarrer',
            'in_progress' => 'En cours',
            'done' => 'Réalisée',
            'cancelled' => 'Annulée',
        ],
    ],
];
