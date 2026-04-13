<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class QvctController extends Controller {
    public function index() {
        return Inertia::render('dashboard/qvct/index', [
            'questionnaires' => $this->defaultQuestionnaires(),
            'stats' => ['moy_global'=>5.0,'alertes'=>3,'reponses'=>6,'taux_reponse'=>69],
        ]);
    }
    private function defaultQuestionnaires(): array {
        return [
            ['id'=>1,'intervenant'=>'Marie Essomba','structure'=>'SAAD Horizon Douala','periode'=>'2026-04','score_bienetre'=>7,'score_charge'=>4,'score_relations'=>8,'score_global'=>6.9,'alerte_rh'=>false,'signaux'=>[]],
            ['id'=>2,'intervenant'=>'Sophie Ateba','structure'=>'SAAD Horizon Douala','periode'=>'2026-04','score_bienetre'=>3,'score_charge'=>8,'score_relations'=>4,'score_global'=>2.8,'alerte_rh'=>true,'signaux'=>['Score bas 2 périodes','Surcharge']],
            ['id'=>3,'intervenant'=>'Jean Koffi','structure'=>'SSIAD Centre Yaoundé','periode'=>'2026-04','score_bienetre'=>6,'score_charge'=>5,'score_relations'=>7,'score_global'=>6.2,'alerte_rh'=>false,'signaux'=>[]],
            ['id'=>4,'intervenant'=>'Bruno Ngono','structure'=>'SSIAD Centre Yaoundé','periode'=>'2026-04','score_bienetre'=>4,'score_charge'=>9,'score_relations'=>5,'score_global'=>3.1,'alerte_rh'=>true,'signaux'=>['Surcharge détectée']],
            ['id'=>5,'intervenant'=>'Amina Fofana','structure'=>'SPASAD Nord','periode'=>'2026-04','score_bienetre'=>8,'score_charge'=>3,'score_relations'=>9,'score_global'=>7.8,'alerte_rh'=>false,'signaux'=>[]],
            ['id'=>6,'intervenant'=>'Pascaline Eko','structure'=>'SPASAD Nord','periode'=>'2026-04','score_bienetre'=>4,'score_charge'=>6,'score_relations'=>4,'score_global'=>3.4,'alerte_rh'=>true,'signaux'=>['Isolement signalé']],
        ];
    }
}