<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditController extends Controller {
    public function index() {
        return Inertia::render('dashboard/audits/index', [
            'audits' => $this->defaultAudits(),
        ]);
    }
    private function defaultAudits(): array {
        return [
            ['id'=>1,'structure'=>'SAAD Horizon Douala','type_grille'=>'HAS Évaluation externe','score_global'=>84,'ecarts_critiques'=>1,'ecarts_majeurs'=>3,'date_audit'=>'05/04/2026','statut'=>'finalise','referent'=>'Sophie Ateba','date_prochain_audit'=>'05/04/2027'],
            ['id'=>2,'structure'=>'SSIAD Centre Yaoundé','type_grille'=>'AFNOR NF X50-056','score_global'=>71,'ecarts_critiques'=>2,'ecarts_majeurs'=>5,'date_audit'=>'08/04/2026','statut'=>'en_cours','referent'=>'Bruno Ngono','date_prochain_audit'=>null],
            ['id'=>3,'structure'=>'SPASAD Nord','type_grille'=>'ISO 9001','score_global'=>0,'ecarts_critiques'=>0,'ecarts_majeurs'=>0,'date_audit'=>'15/04/2026','statut'=>'planifie','referent'=>'Pascaline Eko','date_prochain_audit'=>null],
            ['id'=>4,'structure'=>'SAAD Sud Littoral','type_grille'=>'Caphandeo','score_global'=>91,'ecarts_critiques'=>0,'ecarts_majeurs'=>1,'date_audit'=>'02/04/2026','statut'=>'finalise','referent'=>'Sophie Ateba','date_prochain_audit'=>'02/04/2027'],
            ['id'=>5,'structure'=>'ESAD Centre','type_grille'=>'Interne','score_global'=>78,'ecarts_critiques'=>1,'ecarts_majeurs'=>2,'date_audit'=>'01/04/2026','statut'=>'clos','referent'=>'Bruno Ngono','date_prochain_audit'=>'01/10/2026'],
        ];
    }
}