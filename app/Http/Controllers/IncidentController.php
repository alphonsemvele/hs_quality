<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class IncidentController extends Controller {
    public function index() {
        return Inertia::render('dashboard/incidents/index', [
            'incidents' => $this->defaultIncidents(),
            'stats' => ['declare'=>1,'en_analyse'=>2,'plan_actions'=>2,'clos'=>1,'graves'=>4],
        ]);
    }
    private function defaultIncidents(): array {
        return [
            ['id'=>1,'initials'=>'ME','declarant'=>'Marie Essomba','categorie'=>'Chute','gravite'=>'significatif','statut'=>'en_analyse','structure'=>'SAAD Horizon Douala','date_heure'=>'08/04/2026 08:45','description'=>'Bénéficiaire a chuté en se levant du fauteuil.','notifie_responsable'=>true,'notifie_autorites'=>false],
            ['id'=>2,'initials'=>'JK','declarant'=>'Jean Koffi','categorie'=>'Erreur médicamenteuse','gravite'=>'grave','statut'=>'plan_actions','structure'=>'SSIAD Centre Yaoundé','date_heure'=>'07/04/2026 14:20','description'=>'Mauvais dosage administré au bénéficiaire.','notifie_responsable'=>true,'notifie_autorites'=>false],
            ['id'=>3,'initials'=>'AF','declarant'=>'Amina Fofana','categorie'=>'Agression','gravite'=>'critique','statut'=>'declare','structure'=>'SPASAD Nord','date_heure'=>'08/04/2026 09:10','description'=>'Intervenant agressé verbalement par un tiers au domicile.','notifie_responsable'=>true,'notifie_autorites'=>true],
            ['id'=>4,'initials'=>'PB','declarant'=>'Paul Biya Jr.','categorie'=>'Maltraitance suspectée','gravite'=>'grave','statut'=>'en_analyse','structure'=>'SAAD Sud Littoral','date_heure'=>'07/04/2026 10:00','description'=>'Traces suspectes observées sur le bénéficiaire.','notifie_responsable'=>true,'notifie_autorites'=>true],
            ['id'=>5,'initials'=>'FN','declarant'=>'Fatima Ndiaye','categorie'=>'Chute','gravite'=>'mineur','statut'=>'clos','structure'=>'SAAD Horizon Douala','date_heure'=>'05/04/2026 07:30','description'=>'Petite chute sans blessure.','notifie_responsable'=>true,'notifie_autorites'=>false],
            ['id'=>6,'initials'=>'CT','declarant'=>'Clément Touré','categorie'=>'Accident de travail','gravite'=>'significatif','statut'=>'plan_actions','structure'=>'SSIAD Centre Yaoundé','date_heure'=>'06/04/2026 15:40','description'=>'Intervenant blessé au dos lors d\'un transfert.','notifie_responsable'=>true,'notifie_autorites'=>false],
        ];
    }
}