<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class PlanAmeliorationController extends Controller
{
    public function index()
    {
        return Inertia::render('dashboard/plans-amelioration/index', [
            'pacs' => $this->defaultPacs(),
        ]);
    }

    private function defaultPacs(): array
    {
        return [
            ['id' => 1, 'titre' => 'Mise en place protocole anti-chute', 'structure' => 'SAAD Horizon Douala', 'responsable' => 'Sophie Ateba', 'priorite' => 'haute', 'echeance' => '30/04/2026', 'avancement_pct' => 60, 'statut' => 'en_cours', 'source' => 'incident'],
            ['id' => 2, 'titre' => 'Formation gestion médicamenteuse', 'structure' => 'SSIAD Centre Yaoundé', 'responsable' => 'Bruno Ngono', 'priorite' => 'critique', 'echeance' => '15/04/2026', 'avancement_pct' => 30, 'statut' => 'en_cours', 'source' => 'audit'],
            ['id' => 3, 'titre' => 'Révision du plan d\'accompagnement type', 'structure' => 'SPASAD Nord', 'responsable' => 'Pascaline Eko', 'priorite' => 'moyenne', 'echeance' => '31/05/2026', 'avancement_pct' => 10, 'statut' => 'ouvert', 'source' => 'audit'],
            ['id' => 4, 'titre' => 'Audit interne trimestriel automatisé', 'structure' => 'SAAD Sud Littoral', 'responsable' => 'Jean Koffi', 'priorite' => 'basse', 'echeance' => '30/06/2026', 'avancement_pct' => 0, 'statut' => 'ouvert', 'source' => 'manuel'],
            ['id' => 5, 'titre' => 'Déploiement mode offline sur smartphones', 'structure' => 'Toutes structures', 'responsable' => 'Amina Fofana', 'priorite' => 'haute', 'echeance' => '20/04/2026', 'avancement_pct' => 85, 'statut' => 'en_cours', 'source' => 'manuel'],
            ['id' => 6, 'titre' => 'Renouvellement certifications DEAS', 'structure' => 'SSIAD Centre Yaoundé', 'responsable' => 'Bruno Ngono', 'priorite' => 'haute', 'echeance' => '01/05/2026', 'avancement_pct' => 100, 'statut' => 'realise', 'source' => 'audit'],
        ];
    }
}
