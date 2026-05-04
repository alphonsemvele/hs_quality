<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Communication — French strings
|--------------------------------------------------------------------------
| Phase 2 / M4 (PHASE2_PROGRESS.md row M4.22). UI-facing labels, validation
| overrides, and toast/flash messages for the Communication module
| (messagerie de groupe, fil d'actualité, bibliothèque documentaire, Q&A).
|
| Form requests and the services already throw HttpException with French
| messages where the failure is contextual; this file is for strings
| reused across multiple controllers, components, and notifications.
*/

return [
    // Module label
    'module' => 'Communication',
    'short_module' => 'Comm',

    // Messages
    'message' => [
        'sent' => 'Message envoyé.',
        'edited' => 'Message modifié.',
        'deleted' => 'Message supprimé.',
        'edit_window_elapsed' => "La fenêtre d'édition (5 minutes) est dépassée.",
        'only_author_can_edit' => "Seul l'auteur peut modifier ce message.",
        'attachments_max_5' => 'Vous ne pouvez joindre que 5 fichiers maximum.',
    ],

    // News feed
    'news' => [
        'published' => 'Actualité publiée.',
        'pinned' => 'Actualité épinglée.',
        'unpinned' => 'Actualité désépinglée.',
        'archived' => 'Actualité archivée.',
        'cannot_publish_no_permission' => 'Vous ne pouvez pas publier d\'actualité.',
    ],

    // Documents
    'document' => [
        'uploaded' => 'Document ajouté à la bibliothèque.',
        'upload_too_large' => 'Le document ne doit pas dépasser 25 Mo.',
        'upload_unsupported_mime' => 'Type de fichier non autorisé.',
        'access_denied' => "Vous n'avez pas accès à ce document.",
        'version_label' => 'Version :version',
    ],

    // Q&A forum
    'qa' => [
        'question_asked' => 'Question publiée.',
        'answer_posted' => 'Réponse publiée.',
        'answer_accepted' => 'Réponse marquée comme acceptée.',
        'only_author_can_accept' => "Seul l'auteur de la question peut accepter une réponse.",
        'answer_not_in_question' => "Cette réponse n'appartient pas à la question.",
        'voted' => 'Vote enregistré.',
    ],

    // Discussion groups
    'group' => [
        'created' => 'Groupe créé.',
        'updated' => 'Groupe mis à jour.',
        'archived' => 'Groupe archivé.',
        'cannot_create' => 'Vous ne pouvez pas créer de groupe.',
        'not_a_member' => 'Vous n\'êtes pas membre de ce groupe.',
    ],
];
