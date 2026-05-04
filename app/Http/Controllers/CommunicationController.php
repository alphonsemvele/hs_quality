<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M7 — Communication interne (messagerie WebSocket, fil
 * d'actualité, bibliothèque documentaire).
 *
 * Frontend pages are shipped; backend domain not yet implemented.
 */
class CommunicationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard/communication/index', [
            'messages' => [],
            'channels' => [],
            'documents' => [],
        ]);
    }

    public function sendMessage(): RedirectResponse
    {
        return back()->with('info', 'Module Communication en cours de développement.');
    }
}
