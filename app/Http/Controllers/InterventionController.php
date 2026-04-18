<?php

// ═══════════════════════════════════════════════════════════════
// INTERVENTIONCONTROLLER
// ═══════════════════════════════════════════════════════════════

namespace App\Http\Controllers;

use Inertia\Inertia;

class InterventionController extends Controller
{
    public function index()
    {
        return Inertia::render('dashboard/interventions/index', [
            'interventions' => $this->defaultInterventions(),
            'total' => 6,
            'stats' => ['planifiees' => 2, 'en_cours' => 1, 'realisees' => 3, 'annulees' => 1],
        ]);
    }

    private function defaultInterventions(): array
    {
        return [
            ['id' => 1, 'initials' => 'ME', 'intervenant' => 'Marie Essomba', 'beneficiaire' => 'Pierre Mbarga', 'structure' => 'SAAD Horizon Douala', 'date_heure_debut' => '08/04/2026 08:00', 'date_heure_fin' => '08/04/2026 10:00', 'duree_minutes' => 120, 'statut' => 'realisee', 'compte_rendu' => 'RAS. Bénéficiaire en forme.', 'sync_offline' => false],
            ['id' => 2, 'initials' => 'JK', 'intervenant' => 'Jean Koffi', 'beneficiaire' => 'Élise Ngo', 'structure' => 'SSIAD Centre Yaoundé', 'date_heure_debut' => '08/04/2026 09:30', 'date_heure_fin' => null, 'duree_minutes' => null, 'statut' => 'en_cours', 'compte_rendu' => null, 'sync_offline' => false],
            ['id' => 3, 'initials' => 'AF', 'intervenant' => 'Amina Fofana', 'beneficiaire' => 'Jules Atangana', 'structure' => 'SPASAD Nord', 'date_heure_debut' => '08/04/2026 11:00', 'date_heure_fin' => null, 'duree_minutes' => null, 'statut' => 'planifiee', 'compte_rendu' => null, 'sync_offline' => false],
            ['id' => 4, 'initials' => 'PB', 'intervenant' => 'Paul Biya Jr.', 'beneficiaire' => 'Cécile Fouda', 'structure' => 'SAAD Sud Littoral', 'date_heure_debut' => '07/04/2026 14:00', 'date_heure_fin' => '07/04/2026 16:30', 'duree_minutes' => 150, 'statut' => 'realisee', 'compte_rendu' => 'Soins effectués. Famille présente.', 'sync_offline' => false],
            ['id' => 5, 'initials' => 'FN', 'intervenant' => 'Fatima Ndiaye', 'beneficiaire' => 'Robert Owona', 'structure' => 'SAAD Horizon Douala', 'date_heure_debut' => '08/04/2026 07:00', 'date_heure_fin' => '08/04/2026 09:00', 'duree_minutes' => 120, 'statut' => 'realisee', 'compte_rendu' => 'Ménage + repas préparé.', 'sync_offline' => true],
            ['id' => 6, 'initials' => 'CT', 'intervenant' => 'Clément Touré', 'beneficiaire' => 'Agnès Belinga', 'structure' => 'SSIAD Centre Yaoundé', 'date_heure_debut' => '08/04/2026 13:00', 'date_heure_fin' => null, 'duree_minutes' => null, 'statut' => 'annulee', 'compte_rendu' => null, 'sync_offline' => false],
        ];
    }
}
