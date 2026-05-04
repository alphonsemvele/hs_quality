<?php

use App\Http\Controllers\Api\V1\AuditRunController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BeneficiaryController;
use App\Http\Controllers\Api\V1\CertificationController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\HabilitationController;
use App\Http\Controllers\Api\V1\IncidentController;
use App\Http\Controllers\Api\V1\InterventionController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\NewsFeedController;
use App\Http\Controllers\Api\V1\PacController;
use App\Http\Controllers\Api\V1\QaController;
use App\Http\Controllers\Api\V1\QvctActionPlanController;
use App\Http\Controllers\Api\V1\QvctCampaignController;
use App\Http\Controllers\Api\V1\QvctExchangeRequestController;
use App\Http\Controllers\Api\V1\QvctIndicatorController;
use App\Http\Controllers\Api\V1\QvctJournalController;
use App\Http\Controllers\Api\V1\QvctWeakSignalController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\TrainingAttendanceController;
use App\Http\Controllers\Api\V1\TrainingPlanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1/*
|--------------------------------------------------------------------------
| Mobile REST API consumed by the React Native app (intervenants à domicile).
| Authentication: Sanctum personal access tokens (Bearer header).
| Tenancy: resolved by TenantResolver (appended to the api middleware group
|          in bootstrap/app.php) — no extra 'tenant' middleware needed here.
*/

// ── Unauthenticated ────────────────────────────────────────────────────────
Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:login')
        ->post('auth/login', [AuthController::class, 'login']);
});

// ── Authenticated ──────────────────────────────────────────────────────────
Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:mobile-api'])
    ->group(function (): void {

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Interventions
        Route::get('interventions', [InterventionController::class, 'index']);
        Route::get('interventions/{intervention}', [InterventionController::class, 'show']);

        Route::middleware('idempotent')->group(function (): void {
            Route::post('interventions/{intervention}/check-in', [InterventionController::class, 'checkIn']);
            Route::post('interventions/{intervention}/check-out', [InterventionController::class, 'checkOut']);
            Route::post('interventions/{intervention}/cancel', [InterventionController::class, 'cancel']);
            Route::post('interventions/{intervention}/report', [InterventionController::class, 'submitReport']);
            Route::post('interventions/{intervention}/photos', [InterventionController::class, 'storePhoto']);
            Route::delete('interventions/{intervention}/photos/{photo}', [InterventionController::class, 'destroyPhoto']);
            Route::post('interventions/{intervention}/signatures', [InterventionController::class, 'storeSignature']);
        });

        // Incidents
        Route::get('incidents', [IncidentController::class, 'index']);
        Route::post('incidents', [IncidentController::class, 'store'])->middleware('idempotent');
        Route::get('incidents/{incident}', [IncidentController::class, 'show']);

        // Beneficiaries (read-only on mobile)
        Route::get('beneficiaries', [BeneficiaryController::class, 'index']);
        Route::get('beneficiaries/{beneficiary}', [BeneficiaryController::class, 'show']);

        // QVCT — open-campaigns discovery + anonymous response submission +
        // weak-signal triage (RH-only via QvctWeakSignalPolicy::viewAny).
        // submitResponse is the HTTP fallback for the qvct.submit_response
        // sync op; both go through QvctService::recordResponse so the
        // anonymity invariant holds on either path.
        Route::get('qvct/campaigns', [QvctCampaignController::class, 'index']);
        Route::get('qvct/campaigns/{campaign}', [QvctCampaignController::class, 'show']);
        Route::post('qvct/campaigns/{campaign}/responses', [QvctCampaignController::class, 'submitResponse'])
            ->middleware('idempotent');

        Route::get('qvct/weak-signals', [QvctWeakSignalController::class, 'index']);
        Route::post('qvct/weak-signals/{signal}/acknowledge', [QvctWeakSignalController::class, 'acknowledge'])
            ->middleware('idempotent');

        // Journal — per-user, owner-only by default. RH-shared queue is a
        // separate endpoint with explicit permission gate (qvct.alert.receive
        // + qvct.weak_signal.acknowledge).
        Route::post('qvct/journal', [QvctJournalController::class, 'store'])->middleware('idempotent');
        Route::get('qvct/journal/mine', [QvctJournalController::class, 'mine']);
        Route::get('qvct/journal/shared-with-rh', [QvctJournalController::class, 'sharedWithRh']);

        // Exchange requests — intervenant asks to talk; RH or manager triages.
        Route::post('qvct/exchange-requests', [QvctExchangeRequestController::class, 'store'])->middleware('idempotent');
        Route::get('qvct/exchange-requests/mine', [QvctExchangeRequestController::class, 'mine']);
        Route::get('qvct/exchange-requests/incoming', [QvctExchangeRequestController::class, 'incoming']);
        Route::post('qvct/exchange-requests/{exchangeRequest}/accept', [QvctExchangeRequestController::class, 'accept'])->middleware('idempotent');
        Route::post('qvct/exchange-requests/{exchangeRequest}/schedule', [QvctExchangeRequestController::class, 'schedule'])->middleware('idempotent');
        Route::post('qvct/exchange-requests/{exchangeRequest}/close', [QvctExchangeRequestController::class, 'close'])->middleware('idempotent');

        // Indicators — RH dashboard. Snapshot endpoint triggers the
        // ingestion service on-demand for the caller's tenant.
        Route::get('qvct/indicators', [QvctIndicatorController::class, 'index']);
        Route::put('qvct/indicators/{indicator}', [QvctIndicatorController::class, 'update']);
        Route::post('qvct/indicators/snapshot', [QvctIndicatorController::class, 'snapshot'])->middleware('idempotent');

        // Action plans — RH-driven plans with measurable items.
        Route::get('qvct/action-plans', [QvctActionPlanController::class, 'index']);
        Route::post('qvct/action-plans', [QvctActionPlanController::class, 'store'])->middleware('idempotent');
        Route::get('qvct/action-plans/{plan}', [QvctActionPlanController::class, 'show']);
        Route::post('qvct/action-plans/{plan}/publish', [QvctActionPlanController::class, 'publish'])->middleware('idempotent');
        Route::post('qvct/action-plans/{plan}/close', [QvctActionPlanController::class, 'close'])->middleware('idempotent');
        Route::post('qvct/action-plans/{plan}/items', [QvctActionPlanController::class, 'storeItem'])->middleware('idempotent');
        Route::post('qvct/action-plan-items/{item}/status', [QvctActionPlanController::class, 'updateItemStatus'])->middleware('idempotent');
        Route::post('qvct/action-plan-items/{item}/impact', [QvctActionPlanController::class, 'recordImpact'])->middleware('idempotent');

        // Audits — référent qualité executes a HAS/ISO/AFNOR grid against
        // the structure on a date. Lifecycle invariants live in
        // AuditExecutionService; the route layer just gates and routes.
        Route::get('audit-runs', [AuditRunController::class, 'index']);
        Route::post('audit-runs', [AuditRunController::class, 'store'])->middleware('idempotent');
        Route::get('audit-runs/{auditRun}', [AuditRunController::class, 'show']);
        Route::post('audit-runs/{auditRun}/responses', [AuditRunController::class, 'recordResponse'])->middleware('idempotent');
        Route::post('audit-runs/{auditRun}/finalise', [AuditRunController::class, 'finalise'])->middleware('idempotent');
        Route::post('audit-runs/{auditRun}/generate-pac', [AuditRunController::class, 'generatePac'])->middleware('idempotent');

        // PACs.
        Route::get('pacs', [PacController::class, 'index']);
        Route::get('pacs/{pac}', [PacController::class, 'show']);
        Route::post('pacs/{pac}/close', [PacController::class, 'close'])->middleware('idempotent');
        Route::put('pac-actions/{action}', [PacController::class, 'updateAction']);

        // M4 — Communication. Messages live inside discussion groups; news,
        // documents, and Q&A are structure-scoped. All write paths go through
        // the dedicated services so the same business rules apply on web (M4.17)
        // and mobile (M4.18). Reverb broadcasts (MessagePosted, NewsPostPublished)
        // are dispatched from the services, not the controllers.
        Route::get('communication/groups/{group}/messages', [MessageController::class, 'index']);
        Route::middleware('idempotent')->group(function (): void {
            Route::post('communication/groups/{group}/messages', [MessageController::class, 'store']);
            Route::patch('communication/messages/{message}', [MessageController::class, 'update']);
            Route::delete('communication/messages/{message}', [MessageController::class, 'destroy']);
        });

        Route::get('communication/news', [NewsFeedController::class, 'index']);
        Route::middleware('idempotent')->group(function (): void {
            Route::post('communication/news', [NewsFeedController::class, 'store']);
            Route::post('communication/news/{post}/pin', [NewsFeedController::class, 'pin']);
            Route::post('communication/news/{post}/unpin', [NewsFeedController::class, 'unpin']);
            Route::post('communication/news/{post}/archive', [NewsFeedController::class, 'archive']);
        });

        Route::get('communication/documents', [DocumentController::class, 'index']);
        Route::get('communication/documents/{document}', [DocumentController::class, 'show']);
        Route::get('communication/documents/{document}/download', [DocumentController::class, 'download']);
        Route::post('communication/documents', [DocumentController::class, 'store'])
            ->middleware('idempotent');

        Route::get('communication/qa/questions', [QaController::class, 'index']);
        Route::get('communication/qa/questions/{question}', [QaController::class, 'show']);
        Route::middleware('idempotent')->group(function (): void {
            Route::post('communication/qa/questions', [QaController::class, 'ask']);
            Route::post('communication/qa/questions/{question}/answers', [QaController::class, 'answer']);
            Route::patch('communication/qa/questions/{question}/accept-answer', [QaController::class, 'acceptAnswer']);
            Route::post('communication/qa/answers/{answer}/vote', [QaController::class, 'vote']);
        });

        // M5 — Compétences & formation. Habilitations (lifetime diplomas),
        // certifications (renewable, expiry-alerted), and training plans
        // with sessions/attendances. Tenant scope is enforced by
        // BelongsToStructure on every model; policies enforce role gates.
        Route::get('competencies/habilitations', [HabilitationController::class, 'index']);
        Route::get('competencies/habilitations/{habilitation}', [HabilitationController::class, 'show']);
        Route::middleware('idempotent')->group(function (): void {
            Route::post('competencies/habilitations', [HabilitationController::class, 'store']);
            Route::delete('competencies/habilitations/{habilitation}', [HabilitationController::class, 'destroy']);
        });

        Route::get('competencies/certifications', [CertificationController::class, 'index']);
        Route::get('competencies/certifications/{certification}', [CertificationController::class, 'show']);
        Route::middleware('idempotent')->group(function (): void {
            Route::post('competencies/certifications', [CertificationController::class, 'store']);
            Route::delete('competencies/certifications/{certification}', [CertificationController::class, 'destroy']);
        });

        Route::get('competencies/training-plans', [TrainingPlanController::class, 'index']);
        Route::get('competencies/training-plans/{plan}', [TrainingPlanController::class, 'show']);
        Route::middleware('idempotent')->group(function (): void {
            Route::post('competencies/training-plans', [TrainingPlanController::class, 'store']);
            Route::post('competencies/training-plans/{plan}/publish', [TrainingPlanController::class, 'publish']);
            Route::post('competencies/training-plans/{plan}/archive', [TrainingPlanController::class, 'archive']);
            Route::post('competencies/training-plans/{plan}/sessions', [TrainingPlanController::class, 'addSession']);
        });

        Route::middleware('idempotent')->group(function (): void {
            Route::post('competencies/training-sessions/{session}/register', [TrainingAttendanceController::class, 'register']);
            Route::post('competencies/training-attendances/{attendance}/mark-attended', [TrainingAttendanceController::class, 'markAttended']);
            Route::post('competencies/training-attendances/{attendance}/cancel', [TrainingAttendanceController::class, 'cancel']);
        });

        // Offline sync — flushes the mobile app's queued operations after a
        // network outage. Idempotent at the envelope level (HandleIdempotency)
        // so a network retry of the same batch does not re-execute ops.
        // Throttled separately (sync limiter) because reconnect bursts can
        // legitimately push hundreds of ops in seconds.
        Route::middleware(['idempotent', 'throttle:sync'])
            ->post('sync/batch', [SyncController::class, 'batch']);
    });
