<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class BeneficiaireController extends Controller
{
    public function index()
    {
        return Inertia::render('dashboard/beneficiaires/index', [
            'beneficiaires' => $this->defaultBeneficiaires(),
            'total' => 6,
        ]);
    }

    private function defaultBeneficiaires(): array
    {
        return [
            ['id' => 1, 'initials' => 'PM', 'nom' => 'Mbarga', 'prenom' => 'Pierre', 'structure' => 'SAAD Horizon Douala', 'gir' => 3, 'type_dependance' => 'PA', 'interventions_ce_mois' => 12, 'dernier_incident' => null, 'actif' => true, 'consentement_rgpd' => true],
            ['id' => 2, 'initials' => 'EN', 'nom' => 'Ngo', 'prenom' => 'Élise', 'structure' => 'SSIAD Centre Yaoundé', 'gir' => 2, 'type_dependance' => 'PA', 'interventions_ce_mois' => 8, 'dernier_incident' => '05/04/2026', 'actif' => true, 'consentement_rgpd' => true],
            ['id' => 3, 'initials' => 'JA', 'nom' => 'Atangana', 'prenom' => 'Jules', 'structure' => 'SPASAD Nord', 'gir' => 4, 'type_dependance' => 'PH', 'interventions_ce_mois' => 6, 'dernier_incident' => null, 'actif' => true, 'consentement_rgpd' => true],
            ['id' => 4, 'initials' => 'CF', 'nom' => 'Fouda', 'prenom' => 'Cécile', 'structure' => 'SAAD Sud Littoral', 'gir' => 1, 'type_dependance' => 'PA', 'interventions_ce_mois' => 20, 'dernier_incident' => '07/04/2026', 'actif' => true, 'consentement_rgpd' => true],
            ['id' => 5, 'initials' => 'RO', 'nom' => 'Owona', 'prenom' => 'Robert', 'structure' => 'SAAD Horizon Douala', 'gir' => 5, 'type_dependance' => 'PA', 'interventions_ce_mois' => 4, 'dernier_incident' => null, 'actif' => true, 'consentement_rgpd' => false],
            ['id' => 6, 'initials' => 'AB', 'nom' => 'Belinga', 'prenom' => 'Agnès', 'structure' => 'SSIAD Centre Yaoundé', 'gir' => 3, 'type_dependance' => 'PH', 'interventions_ce_mois' => 10, 'dernier_incident' => null, 'actif' => false, 'consentement_rgpd' => true],
        ];
    }
}
