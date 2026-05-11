<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Compétences & formation — French strings
|--------------------------------------------------------------------------
| Phase 2 / M5 (PHASE2_PROGRESS.md row M5.20). UI-facing labels,
| validation overrides, and toast/flash messages for habilitations,
| certifications, training plans, sessions and attendances.
|
| Form requests and the services already throw HttpException with French
| messages where the failure is contextual; this file is for strings
| reused across multiple controllers, components, and notifications.
*/

return [
    // Module
    'module' => 'Compétences & formation',
    'short_module' => 'Compétences',

    // Habilitations
    'habilitation' => [
        'recorded' => 'Habilitation enregistrée.',
        'renewed' => 'Habilitation renouvelée.',
        'expired' => 'Habilitation expirée.',
        'cannot_renew_trashed' => 'Impossible de renouveler une habilitation supprimée.',
    ],

    // Certifications
    'certification' => [
        'recorded' => 'Certification enregistrée.',
        'expiring_soon' => 'Certification :type expire dans :days jours.',
        'expired' => 'Certification :type expirée.',
        'window_t_minus_90' => 'Renouvellement à prévoir (90 jours).',
        'window_t_minus_30' => 'Renouvellement urgent (30 jours).',
        'window_t_minus_7' => 'Renouvellement très urgent (7 jours).',
        'window_expired' => 'Certification expirée — renouvellement immédiat requis.',
    ],

    // Training plans
    'plan' => [
        'drafted' => 'Plan de formation rédigé.',
        'published' => 'Plan de formation publié.',
        'archived' => 'Plan de formation archivé.',
        'cannot_publish_archived' => 'Impossible de publier un plan archivé.',
        'cannot_add_session_to_archived' => 'Impossible d’ajouter une session à un plan archivé.',
    ],

    // Training sessions
    'session' => [
        'created' => 'Session créée.',
        'updated' => 'Session mise à jour.',
        'deleted' => 'Session supprimée.',
        'invalid_dates' => 'La fin de la session doit être postérieure au début.',
        'capacity_full' => 'Session complète.',
    ],

    // Training attendances
    'attendance' => [
        'registered' => 'Inscription confirmée.',
        'attended' => 'Présence enregistrée.',
        'cancelled' => 'Inscription annulée.',
        'cannot_view' => 'Vous n’avez pas accès à cette inscription.',
    ],
];
