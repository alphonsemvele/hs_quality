<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BeneficiaryController;
use App\Http\Controllers\Api\V1\IncidentController;
use App\Http\Controllers\Api\V1\InterventionController;
use App\Http\Controllers\Api\V1\SyncController;
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

        // Offline sync — flushes the mobile app's queued operations after a
        // network outage. Idempotent at the envelope level (HandleIdempotency)
        // so a network retry of the same batch does not re-execute ops.
        // Throttled separately (sync limiter) because reconnect bursts can
        // legitimately push hundreds of ops in seconds.
        Route::middleware(['idempotent', 'throttle:sync'])
            ->post('sync/batch', [SyncController::class, 'batch']);
    });
