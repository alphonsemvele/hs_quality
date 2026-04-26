<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M7 — Communication interne (messagerie WebSocket, fil
 * d'actualité, bibliothèque documentaire).
 */
class CommunicationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard/coming-soon', [
            'feature' => 'M7 Communication interne',
            'feature_label' => 'Communication',
            'description' => 'Messagerie temps-réel via WebSocket, groupes de discussion, '
                .'fil d\'actualité de la structure, bibliothèque documentaire partagée.',
            'eta' => 'Phase 2 — Mois 7 (T4 2026)',
            'tier_required' => 'pro',
        ]);
    }

    public function sendMessage(): Response
    {
        return $this->index();
    }
}
