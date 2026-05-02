<?php

use App\Http\Controllers\Admin\StructureController as AdminStructureController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\CarePlanController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormationController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IndicateurController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\PlanAmeliorationController;
use App\Http\Controllers\PlannedTaskController;
use App\Http\Controllers\QvctController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Health checks (unauthenticated, no tenant) ───────────────────────────────
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

    Route::middleware(['tenant'])->group(function () {

        // ── Interventions (M1 Month 2 — traçabilité des visites) ─────────────
        // CRUD + three lifecycle endpoints (check-in, check-out, cancel).
        // Lifecycle actions enforce status pre-conditions in InterventionService.
        Route::get('/interventions', [InterventionController::class, 'index'])->name('interventions.index');
        Route::get('/interventions/create', [InterventionController::class, 'create'])->name('interventions.create');
        Route::post('/interventions', [InterventionController::class, 'store'])->name('interventions.store');
        Route::get('/interventions/{intervention}', [InterventionController::class, 'show'])->name('interventions.show');
        Route::get('/interventions/{intervention}/edit', [InterventionController::class, 'edit'])->name('interventions.edit');
        Route::put('/interventions/{intervention}', [InterventionController::class, 'update'])->name('interventions.update');
        Route::delete('/interventions/{intervention}', [InterventionController::class, 'destroy'])->name('interventions.destroy');

        Route::post('/interventions/{intervention}/checkin', [InterventionController::class, 'checkIn'])->name('interventions.checkin');
        Route::post('/interventions/{intervention}/checkout', [InterventionController::class, 'checkOut'])->name('interventions.checkout');
        Route::post('/interventions/{intervention}/cancel', [InterventionController::class, 'cancel'])->name('interventions.cancel');
        Route::post('/interventions/{intervention}/report', [InterventionController::class, 'submitReport'])->name('interventions.report');

        Route::post('/interventions/{intervention}/photos', [InterventionController::class, 'storePhoto'])->name('interventions.photos.store');
        Route::delete('/interventions/{intervention}/photos/{photo}', [InterventionController::class, 'destroyPhoto'])->name('interventions.photos.destroy');
        Route::post('/interventions/{intervention}/signature', [InterventionController::class, 'storeSignature'])->name('interventions.signature.store');

        // ── Incidents & Événements indésirables (M3 — full state machine) ───
        Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
        Route::get('/incidents/create', [IncidentController::class, 'create'])->name('incidents.create');
        Route::post('/incidents', [IncidentController::class, 'store'])->name('incidents.store');
        Route::get('/incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
        Route::put('/incidents/{incident}', [IncidentController::class, 'update'])->name('incidents.update');
        Route::delete('/incidents/{incident}', [IncidentController::class, 'destroy'])->name('incidents.destroy');

        Route::post('/incidents/{incident}/assign', [IncidentController::class, 'assign'])->name('incidents.assign');
        Route::post('/incidents/{incident}/analyse', [IncidentController::class, 'analyse'])->name('incidents.analyse');
        Route::post('/incidents/{incident}/close', [IncidentController::class, 'close'])->name('incidents.close');
        Route::post('/incidents/{incident}/actions', [IncidentController::class, 'storeAction'])->name('incidents.actions.store');

        // ── Bénéficiaires ─────────────────────────────────────────────────────
        Route::get('/beneficiaries', [BeneficiaryController::class, 'index'])->name('beneficiaries.index');
        Route::get('/beneficiaries/create', [BeneficiaryController::class, 'create'])->name('beneficiaries.create');
        Route::post('/beneficiaries', [BeneficiaryController::class, 'store'])->name('beneficiaries.store');
        Route::get('/beneficiaries/{beneficiary}', [BeneficiaryController::class, 'show'])->name('beneficiaries.show');
        Route::get('/beneficiaries/{beneficiary}/edit', [BeneficiaryController::class, 'edit'])->name('beneficiaries.edit');
        Route::put('/beneficiaries/{beneficiary}', [BeneficiaryController::class, 'update'])->name('beneficiaries.update');
        Route::delete('/beneficiaries/{beneficiary}', [BeneficiaryController::class, 'destroy'])->name('beneficiaries.destroy');

        // Dossier médical — every access is audit-logged per CDC §5.2.
        Route::get('/beneficiaries/{beneficiary}/dossier', [BeneficiaryController::class, 'dossier'])
            ->middleware('log_sensitive_read:beneficiary_dossier')
            ->name('beneficiaries.dossier');

        // ── Care plans ────────────────────────────────────────────────────────
        Route::get('/beneficiaries/{beneficiary}/care-plans', [CarePlanController::class, 'indexForBeneficiary'])
            ->name('beneficiaries.care-plans.index');
        Route::get('/beneficiaries/{beneficiary}/care-plans/create', [CarePlanController::class, 'createForBeneficiary'])
            ->name('beneficiaries.care-plans.create');
        Route::post('/beneficiaries/{beneficiary}/care-plans', [CarePlanController::class, 'storeForBeneficiary'])
            ->name('beneficiaries.care-plans.store');

        Route::get('/care-plans/{carePlan}', [CarePlanController::class, 'show'])->name('care-plans.show');
        Route::get('/care-plans/{carePlan}/edit', [CarePlanController::class, 'edit'])->name('care-plans.edit');
        Route::put('/care-plans/{carePlan}', [CarePlanController::class, 'update'])->name('care-plans.update');
        Route::delete('/care-plans/{carePlan}', [CarePlanController::class, 'destroy'])->name('care-plans.destroy');

        Route::post('/care-plans/{carePlan}/activate', [CarePlanController::class, 'activate'])->name('care-plans.activate');
        Route::post('/care-plans/{carePlan}/archive', [CarePlanController::class, 'archive'])->name('care-plans.archive');
        Route::post('/care-plans/{carePlan}/copy', [CarePlanController::class, 'copy'])->name('care-plans.copy');

        // ── Assignations intervenant ↔ bénéficiaire ───────────────────────────
        Route::post('/beneficiaries/{beneficiary}/assignments', [AssignmentController::class, 'store'])
            ->name('beneficiaries.assignments.store');
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])
            ->name('assignments.destroy');

        // ── Tâches planifiées ─────────────────────────────────────────────────
        Route::post('/care-plans/{carePlan}/tasks', [PlannedTaskController::class, 'store'])
            ->name('care-plans.tasks.store');
        Route::put('/tasks/{task}', [PlannedTaskController::class, 'update'])
            ->name('tasks.update');
        Route::delete('/tasks/{task}', [PlannedTaskController::class, 'destroy'])
            ->name('tasks.destroy');

        // ── In-tenant user management (#52 — invite + lifecycle) ──────────────
        // Phase 1 M1 W4 closeout. The tenant's dirigeant (and RH for some
        // ops) invites coordinateurs / intervenants / référents qualité /
        // RH to their structure. Public registration is disabled (Wave 0
        // C2) so this is the ONLY path users take into the system after
        // their tenant is provisioned.
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');
    });

    // ── Qualité ───────────────────────────────────────────────────────────────

    Route::get('/audits', [AuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/create', [AuditController::class, 'create'])->name('audits.create');
    Route::post('/audits', [AuditController::class, 'store'])->name('audits.store');
    Route::get('/audits/{id}', [AuditController::class, 'show'])->name('audits.show');
    Route::put('/audits/{id}', [AuditController::class, 'update'])->name('audits.update');
    Route::put('/audits/{id}/finaliser', [AuditController::class, 'finaliser'])->name('audits.finaliser');

    Route::get('/plans-amelioration', [PlanAmeliorationController::class, 'index'])->name('pac.index');
    Route::get('/plans-amelioration/create', [PlanAmeliorationController::class, 'create'])->name('pac.create');
    Route::post('/plans-amelioration', [PlanAmeliorationController::class, 'store'])->name('pac.store');
    Route::get('/plans-amelioration/{id}', [PlanAmeliorationController::class, 'show'])->name('pac.show');
    Route::put('/plans-amelioration/{id}', [PlanAmeliorationController::class, 'update'])->name('pac.update');

    Route::get('/indicateurs', [IndicateurController::class, 'index'])->name('indicateurs.index');

    // ── QVCT & RH ─────────────────────────────────────────────────────────────
    Route::get('/qvct', [QvctController::class, 'index'])->name('qvct.index');
    Route::get('/qvct/questionnaire', [QvctController::class, 'questionnaire'])->name('qvct.questionnaire');
    Route::post('/qvct', [QvctController::class, 'store'])->name('qvct.store');

    Route::get('/formations', [FormationController::class, 'index'])->name('formations.index');
    Route::post('/formations', [FormationController::class, 'store'])->name('formations.store');
    Route::put('/formations/{id}', [FormationController::class, 'update'])->name('formations.update');

    Route::get('/communication', [CommunicationController::class, 'index'])->name('communication.index');
    Route::post('/communication/message', [CommunicationController::class, 'sendMessage'])->name('communication.send');

    // ── Platform admin (super_admin only) ─────────────────────────────────────
    // Tenants are managed here. NOT inside the `tenant` middleware group —
    // these endpoints operate on the structure rows themselves and do not
    // belong to any tenant. EnsureSuperAdmin returns 404 (not 403) on
    // failure so the surface is invisible to tenant-scoped users.
    Route::middleware(['super_admin'])
        ->prefix('admin/structures')
        ->name('admin.structures.')
        ->group(function (): void {
            Route::get('/', [AdminStructureController::class, 'index'])->name('index');
            Route::get('/create', [AdminStructureController::class, 'create'])->name('create');
            Route::post('/', [AdminStructureController::class, 'store'])->name('store');
            Route::get('/{structure}', [AdminStructureController::class, 'show'])->name('show');
            Route::put('/{structure}', [AdminStructureController::class, 'update'])->name('update');
            Route::delete('/{structure}', [AdminStructureController::class, 'destroy'])->name('destroy');
            Route::post('/{structure}/suspend', [AdminStructureController::class, 'suspend'])->name('suspend');
            Route::post('/{structure}/reactivate', [AdminStructureController::class, 'reactivate'])->name('reactivate');
        });
});
