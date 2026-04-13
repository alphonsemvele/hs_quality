<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\BeneficiaireController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\PlanAmeliorationController;
use App\Http\Controllers\IndicateurController;
use App\Http\Controllers\QvctController;
use App\Http\Controllers\FormationController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\ParametreController;

// ─── Page d'accueil publique ───────────────────────────────────────────────────
Route::get('/', function () {
    return Inertia::render('index');
})->name('home');

// ─── Auth (login, register, logout, password…) ────────────────────────────────
require __DIR__ . '/auth.php';

// ─── Routes protégées ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Terrain ───────────────────────────────────────────────────────────────

    // Interventions
    Route::get('/interventions',            [InterventionController::class, 'index'])   ->name('interventions.index');
    Route::get('/interventions/create',     [InterventionController::class, 'create'])  ->name('interventions.create');
    Route::post('/interventions',           [InterventionController::class, 'store'])   ->name('interventions.store');
    Route::get('/interventions/{id}',       [InterventionController::class, 'show'])    ->name('interventions.show');
    Route::put('/interventions/{id}',       [InterventionController::class, 'update'])  ->name('interventions.update');
    Route::delete('/interventions/{id}',    [InterventionController::class, 'destroy']) ->name('interventions.destroy');

    // Incidents & Événements indésirables
    Route::get('/incidents',                [IncidentController::class, 'index'])       ->name('incidents.index');
    Route::get('/incidents/create',         [IncidentController::class, 'create'])      ->name('incidents.create');
    Route::post('/incidents',               [IncidentController::class, 'store'])       ->name('incidents.store');
    Route::get('/incidents/{id}',           [IncidentController::class, 'show'])        ->name('incidents.show');
    Route::put('/incidents/{id}',           [IncidentController::class, 'update'])      ->name('incidents.update');
    Route::put('/incidents/{id}/statut',    [IncidentController::class, 'updateStatut'])->name('incidents.statut');
    Route::delete('/incidents/{id}',        [IncidentController::class, 'destroy'])     ->name('incidents.destroy');

    // Bénéficiaires
    Route::get('/beneficiaires',            [BeneficiaireController::class, 'index'])   ->name('beneficiaires.index');
    Route::get('/beneficiaires/create',     [BeneficiaireController::class, 'create'])  ->name('beneficiaires.create');
    Route::post('/beneficiaires',           [BeneficiaireController::class, 'store'])   ->name('beneficiaires.store');
    Route::get('/beneficiaires/{id}',       [BeneficiaireController::class, 'show'])    ->name('beneficiaires.show');
    Route::put('/beneficiaires/{id}',       [BeneficiaireController::class, 'update'])  ->name('beneficiaires.update');

    // ── Qualité ───────────────────────────────────────────────────────────────

    // Audits & Conformité
    Route::get('/audits',                   [AuditController::class, 'index'])          ->name('audits.index');
    Route::get('/audits/create',            [AuditController::class, 'create'])         ->name('audits.create');
    Route::post('/audits',                  [AuditController::class, 'store'])          ->name('audits.store');
    Route::get('/audits/{id}',              [AuditController::class, 'show'])           ->name('audits.show');
    Route::put('/audits/{id}',              [AuditController::class, 'update'])         ->name('audits.update');
    Route::put('/audits/{id}/finaliser',    [AuditController::class, 'finaliser'])      ->name('audits.finaliser');

    // Plans d'amélioration continue (PAC)
    Route::get('/plans-amelioration',       [PlanAmeliorationController::class, 'index'])   ->name('pac.index');
    Route::get('/plans-amelioration/create',[PlanAmeliorationController::class, 'create'])  ->name('pac.create');
    Route::post('/plans-amelioration',      [PlanAmeliorationController::class, 'store'])   ->name('pac.store');
    Route::get('/plans-amelioration/{id}',  [PlanAmeliorationController::class, 'show'])    ->name('pac.show');
    Route::put('/plans-amelioration/{id}',  [PlanAmeliorationController::class, 'update'])  ->name('pac.update');

    // Indicateurs & KPIs
    Route::get('/indicateurs',              [IndicateurController::class, 'index'])     ->name('indicateurs.index');

    // ── QVCT & RH ─────────────────────────────────────────────────────────────

    // Baromètre QVCT
    Route::get('/qvct',                     [QvctController::class, 'index'])           ->name('qvct.index');
    Route::get('/qvct/questionnaire',       [QvctController::class, 'questionnaire'])   ->name('qvct.questionnaire');
    Route::post('/qvct',                    [QvctController::class, 'store'])           ->name('qvct.store');

    // Formations & Habilitations
    Route::get('/formations',               [FormationController::class, 'index'])      ->name('formations.index');
    Route::post('/formations',              [FormationController::class, 'store'])      ->name('formations.store');
    Route::put('/formations/{id}',          [FormationController::class, 'update'])     ->name('formations.update');

    // Communication interne
    Route::get('/communication',            [CommunicationController::class, 'index'])  ->name('communication.index');
    Route::post('/communication/message',   [CommunicationController::class, 'sendMessage'])->name('communication.send');

    // ── Administration ────────────────────────────────────────────────────────

    // Structures
    Route::get('/structures',               [StructureController::class, 'index'])      ->name('structures.index');
    Route::get('/structures/{id}',          [StructureController::class, 'show'])       ->name('structures.show');
    Route::post('/structures',              [StructureController::class, 'store'])      ->name('structures.store');
    Route::put('/structures/{id}',          [StructureController::class, 'update'])     ->name('structures.update');

    // Utilisateurs
    Route::get('/utilisateurs',             [UtilisateurController::class, 'index'])    ->name('utilisateurs.index');
    Route::get('/utilisateurs/{id}',        [UtilisateurController::class, 'show'])     ->name('utilisateurs.show');
    Route::put('/utilisateurs/{id}',        [UtilisateurController::class, 'update'])   ->name('utilisateurs.update');

    // Rapports
    Route::get('/rapports',                 [RapportController::class, 'index'])        ->name('rapports.index');
    Route::post('/rapports/generer',        [RapportController::class, 'generer'])      ->name('rapports.generer');

    // Paramètres
    Route::get('/parametres',               [ParametreController::class, 'index'])      ->name('parametres.index');
    Route::put('/parametres',               [ParametreController::class, 'update'])     ->name('parametres.update');

});