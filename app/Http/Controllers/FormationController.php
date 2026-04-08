<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class FormationController extends Controller {
    public function index() {
        return Inertia::render('dashboard/formations/index', [
            'formations' => $this->defaultFormations(),
        ]);
    }
    private function defaultFormations(): array {
        return [
            ['id'=>1,'intervenant'=>'Marie Essomba','structure'=>'SAAD Horizon Douala','intitule'=>'DEAS — Diplôme État Aide-Soignant','type_formation'=>'certification','date_debut'=>'01/09/2022','date_expiration'=>null,'statut'=>'validee','jours_avant_expiration'=>null],
            ['id'=>2,'intervenant'=>'Jean Koffi','structure'=>'SSIAD Centre Yaoundé','intitule'=>'Gestes et soins d\'urgence','type_formation'=>'habilitation','date_debut'=>'15/03/2024','date_expiration'=>'15/03/2026','statut'=>'validee','jours_avant_expiration'=>7],
            ['id'=>3,'intervenant'=>'Bruno Ngono','structure'=>'SSIAD Centre Yaoundé','intitule'=>'Prévention des risques psychosociaux','type_formation'=>'presentiel','date_debut'=>'10/04/2026','date_expiration'=>null,'statut'=>'en_cours','jours_avant_expiration'=>null],
            ['id'=>4,'intervenant'=>'Amina Fofana','structure'=>'SPASAD Nord','intitule'=>'Module e-learning : Bientraitance','type_formation'=>'elearning','date_debut'=>'05/04/2026','date_expiration'=>null,'statut'=>'validee','jours_avant_expiration'=>null],
            ['id'=>5,'intervenant'=>'Sophie Ateba','structure'=>'SAAD Horizon Douala','intitule'=>'Transferts et manutention bénéficiaires','type_formation'=>'habilitation','date_debut'=>'20/05/2025','date_expiration'=>'20/05/2026','statut'=>'validee','jours_avant_expiration'=>42],
            ['id'=>6,'intervenant'=>'Clément Touré','structure'=>'SSIAD Centre Yaoundé','intitule'=>'Certification HACCP alimentation','type_formation'=>'certification','date_debut'=>'01/04/2025','date_expiration'=>'01/04/2026','statut'=>'echouee','jours_avant_expiration'=>-7],
            ['id'=>7,'intervenant'=>'Pascaline Eko','structure'=>'SPASAD Nord','intitule'=>'Accompagnement personnes Alzheimer','type_formation'=>'tutore','date_debut'=>'15/04/2026','date_expiration'=>null,'statut'=>'planifiee','jours_avant_expiration'=>null],
        ];
    }
}
 