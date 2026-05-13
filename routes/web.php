<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\StructureController as AdminStructureController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuditGridController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CarePlanController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormationController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IndicateurController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlanAmeliorationController;
use App\Http\Controllers\PlannedTaskController;
use App\Http\Controllers\QvctCampaignController;
use App\Http\Controllers\QvctController;
use App\Http\Controllers\QvctQuestionnaireController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Health checks (unauthenticated, no tenant) ───────────────────────────────
Route::get('/health/live', [HealthController::class, 'live'])->name('health.live');
Route::get('/health/ready', [HealthController::class, 'ready'])->name('health.ready');

// ─── Pages publiques (marketing) ──────────────────────────────────────────────
Route::get('/', function () {
    return Inertia::render('index');
})->name('home');

Route::get('/fonctionnalites', function () {
    return Inertia::render('marketing/features');
})->name('marketing.features');

Route::get('/conformite', function () {
    return Inertia::render('marketing/compliance');
})->name('marketing.compliance');

Route::get('/tarifs', function () {
    return Inertia::render('marketing/tarifs');
})->name('marketing.tarifs');

Route::get('/clients', function () {
    return Inertia::render('marketing/clients');
})->name('marketing.clients');

Route::get('/changelog', function () {
    return Inertia::render('marketing/changelog');
})->name('marketing.changelog');

Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:contact-form')
    ->name('contact.store');

// ─── Pages légales ────────────────────────────────────────────────────────────
Route::get('/mentions-legales', function () {
    return Inertia::render('marketing/legal-mentions');
})->name('marketing.legal-mentions');

Route::get('/confidentialite', function () {
    return Inertia::render('marketing/privacy');
})->name('marketing.privacy');

Route::get('/cgu', function () {
    return Inertia::render('marketing/cgu');
})->name('marketing.cgu');

Route::get('/accessibilite', function () {
    return Inertia::render('marketing/accessibility');
})->name('marketing.accessibility');

// ─── Auth (login, register, logout, password…) ────────────────────────────────
require __DIR__.'/auth.php';

// ─── Routes protégées ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/profile', fn () => Inertia::render('dashboard/profile'))->name('profile');
    Route::get('/dashboard/profile/mfa-setup', fn () => Inertia::render('dashboard/profile/mfa-setup'))->name('profile.mfa-setup');
    Route::get('/dashboard/profile/notifications', fn () => Inertia::render('dashboard/profile/notifications-preferences'))->name('profile.notifications');
    Route::get('/dashboard/profile/api-tokens', fn () => Inertia::render('dashboard/profile/api-tokens'))->name('profile.api-tokens');
    Route::get('/dashboard/profile/sessions', fn () => Inertia::render('dashboard/profile/sessions'))->name('profile.sessions');
    Route::get('/dashboard/onboarding', fn () => Inertia::render('dashboard/onboarding'))->name('onboarding');
    Route::get('/dashboard/aide/glossaire', fn () => Inertia::render('dashboard/aide/glossaire'))->name('aide.glossaire');

    // Notifications (in-app — see HandleInertiaRequests for shared payload)
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    // Cross-domain quick search for the Cmd+K command palette
    Route::middleware('tenant')->get('/search/quick', [SearchController::class, 'quick'])->name('search.quick');

    // Billing & subscription (Inertia surface — write paths go through Api\V1\SubscriptionController)
    Route::middleware('tenant')->group(function () {
        Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
        Route::post('/billing/change-plan', [BillingController::class, 'changePlan'])->name('billing.change-plan');
        Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');

        Route::get('/settings/structure', [SettingsController::class, 'structure'])->name('settings.structure');
        Route::put('/settings/contact', [SettingsController::class, 'updateContact'])->name('settings.contact.update');
    });

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
        // bulk route declared before parametric `/{intervention}/cancel`
        // to avoid wildcard match on the literal "bulk" segment.
        Route::post('/interventions/bulk/cancel', [InterventionController::class, 'bulkCancel'])->name('interventions.bulk.cancel');
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

        Route::get('/beneficiaries/{beneficiary}/timeline', [BeneficiaryController::class, 'timeline'])
            ->name('beneficiaries.timeline');

        Route::get('/beneficiaries/{beneficiary}/contacts', [BeneficiaryController::class, 'contacts'])
            ->name('beneficiaries.contacts');
        Route::put('/beneficiaries/{beneficiary}/contacts', [BeneficiaryController::class, 'updateContacts'])
            ->name('beneficiaries.contacts.update');

        Route::get('/beneficiaries/{beneficiary}/satisfaction', [BeneficiaryController::class, 'satisfaction'])
            ->name('beneficiaries.satisfaction');
        Route::post('/beneficiaries/{beneficiary}/satisfaction', [BeneficiaryController::class, 'storeSatisfaction'])
            ->name('beneficiaries.satisfaction.store');

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
        Route::post('/care-plans/{carePlan}/tasks/reorder', [PlannedTaskController::class, 'reorder'])
            ->name('care-plans.tasks.reorder');
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

    // ── Qualité (M6 — Audits + Plans d'Amélioration Continue) ─────────────────
    Route::middleware(['tenant'])->group(function () {
        Route::get('/audits', [AuditController::class, 'index'])->name('audits.index');
        // Audit grids library (read-only — declare before /audits/{audit}
        // so the literal "grids" segment is not matched as a UUID).
        Route::get('/audits/grids', [AuditGridController::class, 'index'])->name('audits.grids.index');
        Route::get('/audits/grids/{auditGrid}', [AuditGridController::class, 'show'])->name('audits.grids.show');
        Route::get('/audits/has-preparation', [AuditController::class, 'hasPreparation'])->name('audits.has-preparation');

        Route::get('/audits/create', [AuditController::class, 'create'])->name('audits.create');
        Route::post('/audits', [AuditController::class, 'store'])->name('audits.store');
        Route::get('/audits/{audit}', [AuditController::class, 'show'])->name('audits.show');
        Route::get('/audits/{audit}/edit', [AuditController::class, 'edit'])->name('audits.edit');
        Route::put('/audits/{audit}', [AuditController::class, 'update'])->name('audits.update');
        Route::post('/audits/{audit}/finaliser', [AuditController::class, 'finaliser'])->name('audits.finaliser');
        Route::post('/audits/{audit}/cancel', [AuditController::class, 'cancel'])->name('audits.cancel');
        Route::post('/audits/{audit}/ecarts', [AuditController::class, 'storeEcart'])->name('audits.ecarts.store');
        Route::delete('/audits/{audit}/ecarts/{ecart}', [AuditController::class, 'destroyEcart'])->name('audits.ecarts.destroy');

        Route::get('/plans-amelioration', [PlanAmeliorationController::class, 'index'])->name('pac.index');
        Route::get('/plans-amelioration/create', [PlanAmeliorationController::class, 'create'])->name('pac.create');
        Route::post('/plans-amelioration', [PlanAmeliorationController::class, 'store'])->name('pac.store');
        Route::get('/plans-amelioration/{plan}', [PlanAmeliorationController::class, 'show'])->name('pac.show');
        Route::get('/plans-amelioration/{plan}/edit', [PlanAmeliorationController::class, 'edit'])->name('pac.edit');
        Route::put('/plans-amelioration/{plan}', [PlanAmeliorationController::class, 'update'])->name('pac.update');
        Route::post('/plans-amelioration/{plan}/close', [PlanAmeliorationController::class, 'close'])->name('pac.close');
        Route::post('/plans-amelioration/{plan}/cancel', [PlanAmeliorationController::class, 'cancel'])->name('pac.cancel');
        Route::post('/plans-amelioration/{plan}/actions', [PlanAmeliorationController::class, 'storeAction'])->name('pac.actions.store');
        Route::put('/plans-amelioration/{plan}/actions/{action}', [PlanAmeliorationController::class, 'updateAction'])->name('pac.actions.update');
        Route::post('/plans-amelioration/{plan}/actions/{action}/done', [PlanAmeliorationController::class, 'markActionDone'])->name('pac.actions.done');
        Route::delete('/plans-amelioration/{plan}/actions/{action}', [PlanAmeliorationController::class, 'destroyAction'])->name('pac.actions.destroy');
    });

    Route::get('/indicateurs', [IndicateurController::class, 'index'])->name('indicateurs.index');

    // RGPD Art. 30 — registre d'audit (owen-it/laravel-auditing)
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

    // ── QVCT & RH ─────────────────────────────────────────────────────────────
    // Phase 1 placeholder routes (kept for tier-gate UX until M3 frontend lands).
    Route::get('/qvct', [QvctController::class, 'index'])->name('qvct.index');
    Route::get('/qvct/questionnaire', [QvctController::class, 'questionnaire'])->name('qvct.questionnaire');
    Route::post('/qvct', [QvctController::class, 'store'])->name('qvct.store');

    // Wave B extension — read-only demo surfaces wired to QvctController until
    // dedicated controllers (weak-signal, indicator, action-plan, journal,
    // exchange) gain Inertia entry points alongside the existing API surface.
    Route::get('/qvct/weak-signals', [QvctController::class, 'weakSignals'])->name('qvct.weak-signals');
    Route::post('/qvct/weak-signals/{id}/acknowledge', [QvctController::class, 'acknowledgeWeakSignal'])->name('qvct.weak-signals.acknowledge');
    Route::get('/qvct/indicators', [QvctController::class, 'indicators'])->name('qvct.indicators');
    Route::get('/qvct/action-plans', [QvctController::class, 'actionPlans'])->name('qvct.action-plans');
    Route::get('/qvct/journal', [QvctController::class, 'journal'])->name('qvct.journal');
    Route::post('/qvct/journal', [QvctController::class, 'storeJournalEntry'])->name('qvct.journal.store');
    Route::get('/qvct/exchanges', [QvctController::class, 'exchanges'])->name('qvct.exchanges');
    Route::post('/qvct/exchanges', [QvctController::class, 'storeExchange'])->name('qvct.exchanges.store');

    // Phase 2 / M3 — questionnaire + campaign management.
    Route::prefix('qvct')->name('qvct.')->group(function (): void {
        Route::get('/questionnaires', [QvctQuestionnaireController::class, 'index'])->name('questionnaires.index');
        Route::get('/questionnaires/create', [QvctQuestionnaireController::class, 'create'])->name('questionnaires.create');
        Route::post('/questionnaires', [QvctQuestionnaireController::class, 'store'])->name('questionnaires.store');
        Route::get('/questionnaires/{questionnaire}', [QvctQuestionnaireController::class, 'show'])->name('questionnaires.show');
        Route::post('/questionnaires/{questionnaire}/archive', [QvctQuestionnaireController::class, 'archive'])->name('questionnaires.archive');
        Route::delete('/questionnaires/{questionnaire}', [QvctQuestionnaireController::class, 'destroy'])->name('questionnaires.destroy');

        Route::get('/campaigns', [QvctCampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/create', [QvctCampaignController::class, 'create'])->name('campaigns.create');
        Route::get('/campaigns/{campaign}', [QvctCampaignController::class, 'show'])->name('campaigns.show');
        Route::post('/questionnaires/{questionnaire}/campaigns', [QvctCampaignController::class, 'launch'])->name('campaigns.launch');
        Route::post('/campaigns/{campaign}/close', [QvctCampaignController::class, 'close'])->name('campaigns.close');
    });

    Route::get('/formations', [FormationController::class, 'index'])->name('formations.index');
    Route::get('/formations/competencies/mine', [FormationController::class, 'myCompetencies'])->name('formations.competencies.mine');
    Route::get('/formations/sessions/{id}', [FormationController::class, 'showSession'])->name('formations.sessions.show');
    Route::post('/formations', [FormationController::class, 'store'])->name('formations.store');
    Route::put('/formations/{id}', [FormationController::class, 'update'])->name('formations.update');

    Route::get('/communication', [CommunicationController::class, 'index'])->name('communication.index');
    Route::post('/communication/groups/{group}/messages', [CommunicationController::class, 'sendMessage'])
        ->name('communication.send');
    Route::post('/communication/news', [CommunicationController::class, 'publishNews'])
        ->name('communication.news.publish');
    Route::post('/communication/documents', [CommunicationController::class, 'uploadDocument'])
        ->name('communication.documents.upload');
    Route::get('/communication/documents/{document}/download', [CommunicationController::class, 'downloadDocument'])
        ->name('communication.documents.download');

    // ── Platform admin (super_admin only) ─────────────────────────────────────
    // Tenants are managed here. NOT inside the `tenant` middleware group —
    // these endpoints operate on the structure rows themselves and do not
    // belong to any tenant. EnsureSuperAdmin returns 404 (not 403) on
    // failure so the surface is invisible to tenant-scoped users.
    // ── Platform admin dashboard (super_admin only) ──────────────────────────
    // Cross-tenant KPI surface for platform operators. Tenant-scoped users
    // 404 here (EnsureSuperAdmin) — the page does not exist for them.
    Route::middleware(['super_admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

            Route::get('/feature-flags', [FeatureFlagController::class, 'index'])->name('feature-flags.index');
            Route::post('/feature-flags/toggle', [FeatureFlagController::class, 'toggle'])->name('feature-flags.toggle');

            Route::get('/system-health', [SystemHealthController::class, 'index'])->name('system-health.index');
        });

    Route::middleware(['super_admin'])
        ->prefix('admin/structures')
        ->name('admin.structures.')
        ->group(function (): void {
            Route::get('/', [AdminStructureController::class, 'index'])->name('index');
            Route::get('/create', [AdminStructureController::class, 'create'])->name('create');
            Route::post('/', [AdminStructureController::class, 'store'])->name('store');
            Route::get('/{structure}', [AdminStructureController::class, 'show'])->name('show');
            Route::get('/{structure}/audit-trail', [AdminStructureController::class, 'auditTrail'])->name('audit-trail');
            Route::get('/{structure}/edit', [AdminStructureController::class, 'edit'])->name('edit');
            Route::put('/{structure}', [AdminStructureController::class, 'update'])->name('update');
            Route::delete('/{structure}', [AdminStructureController::class, 'destroy'])->name('destroy');
            Route::post('/{structure}/suspend', [AdminStructureController::class, 'suspend'])->name('suspend');
            Route::post('/{structure}/reactivate', [AdminStructureController::class, 'reactivate'])->name('reactivate');
        });
});
