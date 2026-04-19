<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormationController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IndicateurController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\PlanAmeliorationController;
use App\Http\Controllers\QvctController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Health checks (unauthenticated, no tenant) ───────────────────────────────
// Laravel's built-in /up remains for backward compat; /health/* are the
// canonical ALB/ECS probe targets.
Route::get('/health/live', [HealthController::class, 'live'])->name('health.live');
Route::get('/health/ready', [HealthController::class, 'ready'])->name('health.ready');

// ─── Page d'accueil publique ───────────────────────────────────────────────────
Route::get('/', function () {
    return Inertia::render('index');
})->name('home');

// ─── Auth (login, register, logout, password…) ────────────────────────────────
require __DIR__.'/auth.php';

// ─── Routes protégées ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Terrain ───────────────────────────────────────────────────────────────

    // Interventions
    Route::get('/interventions', [InterventionController::class, 'index'])->name('interventions.index');
    Route::get('/interventions/create', [InterventionController::class, 'create'])->name('interventions.create');
    Route::post('/interventions', [InterventionController::class, 'store'])->name('interventions.store');
    Route::get('/interventions/{id}', [InterventionController::class, 'show'])->name('interventions.show');
    Route::put('/interventions/{id}', [InterventionController::class, 'update'])->name('interventions.update');
    Route::delete('/interventions/{id}', [InterventionController::class, 'destroy'])->name('interventions.destroy');

    // Incidents & Événements indésirables
    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/incidents/create', [IncidentController::class, 'create'])->name('incidents.create');
    Route::post('/incidents', [IncidentController::class, 'store'])->name('incidents.store');
    Route::get('/incidents/{id}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::put('/incidents/{id}', [IncidentController::class, 'update'])->name('incidents.update');
    Route::put('/incidents/{id}/statut', [IncidentController::class, 'updateStatut'])->name('incidents.statut');
    Route::delete('/incidents/{id}', [IncidentController::class, 'destroy'])->name('incidents.destroy');

    // Bénéficiaires (CRUD + dossier médical with sensitive-read audit)
    Route::middleware(['tenant'])->group(function () {
        Route::get('/beneficiaries', [BeneficiaryController::class, 'index'])->name('beneficiaries.index');
        Route::get('/beneficiaries/create', [BeneficiaryController::class, 'create'])->name('beneficiaries.create');
        Route::post('/beneficiaries', [BeneficiaryController::class, 'store'])->name('beneficiaries.store');
        Route::get('/beneficiaries/{beneficiary}', [BeneficiaryController::class, 'show'])->name('beneficiaries.show');
        Route::get('/beneficiaries/{beneficiary}/edit', [BeneficiaryController::class, 'edit'])->name('beneficiaries.edit');
        Route::put('/beneficiaries/{beneficiary}', [BeneficiaryController::class, 'update'])->name('beneficiaries.update');
        Route::delete('/beneficiaries/{beneficiary}', [BeneficiaryController::class, 'destroy'])->name('beneficiaries.destroy');

        // Dossier médical — encrypted health-data fields. Every access is
        // audit-logged via log_sensitive_read middleware per CDC §5.2.
        Route::get('/beneficiaries/{beneficiary}/dossier', [BeneficiaryController::class, 'dossier'])
            ->middleware('log_sensitive_read:beneficiary_dossier')
            ->name('beneficiaries.dossier');
    });

    // ── Qualité ───────────────────────────────────────────────────────────────

    // Audits & Conformité
    Route::get('/audits', [AuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/create', [AuditController::class, 'create'])->name('audits.create');
    Route::post('/audits', [AuditController::class, 'store'])->name('audits.store');
    Route::get('/audits/{id}', [AuditController::class, 'show'])->name('audits.show');
    Route::put('/audits/{id}', [AuditController::class, 'update'])->name('audits.update');
    Route::put('/audits/{id}/finaliser', [AuditController::class, 'finaliser'])->name('audits.finaliser');

    // Plans d'amélioration continue (PAC)
    Route::get('/plans-amelioration', [PlanAmeliorationController::class, 'index'])->name('pac.index');
    Route::get('/plans-amelioration/create', [PlanAmeliorationController::class, 'create'])->name('pac.create');
    Route::post('/plans-amelioration', [PlanAmeliorationController::class, 'store'])->name('pac.store');
    Route::get('/plans-amelioration/{id}', [PlanAmeliorationController::class, 'show'])->name('pac.show');
    Route::put('/plans-amelioration/{id}', [PlanAmeliorationController::class, 'update'])->name('pac.update');

    // Indicateurs & KPIs
    Route::get('/indicateurs', [IndicateurController::class, 'index'])->name('indicateurs.index');

    // ── QVCT & RH ─────────────────────────────────────────────────────────────

    // Baromètre QVCT
    Route::get('/qvct', [QvctController::class, 'index'])->name('qvct.index');
    Route::get('/qvct/questionnaire', [QvctController::class, 'questionnaire'])->name('qvct.questionnaire');
    Route::post('/qvct', [QvctController::class, 'store'])->name('qvct.store');

    // Formations & Habilitations
    Route::get('/formations', [FormationController::class, 'index'])->name('formations.index');
    Route::post('/formations', [FormationController::class, 'store'])->name('formations.store');
    Route::put('/formations/{id}', [FormationController::class, 'update'])->name('formations.update');

    // Communication interne
    Route::get('/communication', [CommunicationController::class, 'index'])->name('communication.index');
    Route::post('/communication/message', [CommunicationController::class, 'sendMessage'])->name('communication.send');

    // ── Administration ────────────────────────────────────────────────────────
    // Structure, Utilisateur, Rapport, and Parametre routes will be rebuilt
    // in Phase 1 alongside their controllers. They previously referenced
    // controllers that were never implemented.

});
