<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 2 Module M7 — Communication interne.
 */
class CommunicationController extends Controller
{
    public function index(): Response
    {
        $demo = config('app.env') === 'local';

        return Inertia::render('dashboard/communication/index', [
            'messages' => $demo ? $this->demoMessages() : [],
            'channels' => $demo ? $this->demoChannels() : [],
            'documents' => $demo ? $this->demoDocuments() : [],
        ]);
    }

    public function sendMessage(): RedirectResponse
    {
        return back()->with('info', 'Module Communication en cours de développement.');
    }

    /** @return list<array<string, mixed>> */
    private function demoMessages(): array
    {
        return [
            ['id' => 'm1', 'author' => 'Sophie Martin', 'initials' => 'SM', 'content' => 'Rappel : réunion d\'équipe ce vendredi à 14h en visio. Ordre du jour : bilan Q1 et planification Q2.', 'channel' => 'general', 'created_at' => 'Aujourd\'hui, 09:15'],
            ['id' => 'm2', 'author' => 'Claire Bernard', 'initials' => 'CB', 'content' => 'Le nouveau protocole de transmission numérique est en ligne. Merci de le consulter et de confirmer la prise de connaissance avant vendredi.', 'channel' => 'qualite', 'created_at' => 'Hier, 16:30'],
            ['id' => 'm3', 'author' => 'Thomas Dupont', 'initials' => 'TD', 'content' => 'Planning de la semaine prochaine publié. Marie et Luc, merci de vérifier vos créneaux et signaler tout conflit.', 'channel' => 'coordination', 'created_at' => 'Hier, 11:00'],
            ['id' => 'm4', 'author' => 'Anne Petit', 'initials' => 'AP', 'content' => 'Les sessions de formation PSC1 sont ouvertes à l\'inscription. Priorité aux intervenants dont la certification expire avant septembre.', 'channel' => 'rh', 'created_at' => '02/05, 14:20'],
            ['id' => 'm5', 'author' => 'Marie Leclerc', 'initials' => 'ML', 'content' => 'Retour terrain : le tapis antidérapant chez Mme D. est bien en place, elle se sent rassurée.', 'channel' => 'terrain', 'created_at' => '01/05, 17:45'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoChannels(): array
    {
        return [
            ['id' => 'ch1', 'name' => 'Général', 'nb_members' => 6, 'last_activity' => 'Aujourd\'hui'],
            ['id' => 'ch2', 'name' => 'Coordination', 'nb_members' => 3, 'last_activity' => 'Hier'],
            ['id' => 'ch3', 'name' => 'Qualité & Audits', 'nb_members' => 2, 'last_activity' => 'Hier'],
            ['id' => 'ch4', 'name' => 'Terrain', 'nb_members' => 4, 'last_activity' => '01/05'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function demoDocuments(): array
    {
        return [
            ['id' => 'd1', 'title' => 'Protocole de transmission numérique v2', 'type' => 'PDF', 'uploaded_by' => 'Claire Bernard', 'uploaded_at' => '12/04/2026'],
            ['id' => 'd2', 'title' => 'Check-list évaluation domicile', 'type' => 'PDF', 'uploaded_by' => 'Claire Bernard', 'uploaded_at' => '28/04/2026'],
            ['id' => 'd3', 'title' => 'Planning Mai 2026', 'type' => 'Excel', 'uploaded_by' => 'Thomas Dupont', 'uploaded_at' => '30/04/2026'],
            ['id' => 'd4', 'title' => 'Protocole médicamenteux révisé', 'type' => 'PDF', 'uploaded_by' => 'Claire Bernard', 'uploaded_at' => '28/03/2026'],
        ];
    }
}
