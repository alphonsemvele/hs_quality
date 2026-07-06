<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gdpr;

use App\Enums\AccountDeletionStatus;
use App\Enums\DataExportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gdpr\RequestAccountDeletionRequest;
use App\Http\Requests\Gdpr\RequestDataExportRequest;
use App\Jobs\Gdpr\GenerateDataExportJob;
use App\Models\AccountDeletionRequest;
use App\Models\DataExportRequest;
use App\Services\Gdpr\DataExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * GDPR self-service surface for the authenticated user — listing past
 * export requests, triggering a new one, downloading a ready archive,
 * and initiating an account deletion (article 17).
 *
 * Tenant scoping is enforced by the model's BelongsToStructure trait,
 * so a request issued in structure A is invisible from structure B.
 */
class DataExportController extends Controller
{
    public function show(): Response
    {
        $user = auth()->user();

        $requests = DataExportRequest::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (DataExportRequest $r) => [
                'id' => $r->id,
                'status' => $r->status->value,
                'status_label' => $r->status->label(),
                'created_at' => $r->created_at?->toIso8601String(),
                'processed_at' => $r->processed_at?->toIso8601String(),
                'expires_at' => $r->expires_at?->toIso8601String(),
                'archive_size_bytes' => $r->archive_size_bytes,
                'is_downloadable' => $r->status->isDownloadable() && ! $this->isExpired($r),
                'failure_reason' => $r->failure_reason,
            ])
            ->all();

        $deletionRequest = AccountDeletionRequest::query()
            ->where('user_id', $user->id)
            ->where('status', AccountDeletionStatus::Pending->value)
            ->orderByDesc('created_at')
            ->first();

        return Inertia::render('dashboard/profile/gdpr', [
            'requests' => $requests,
            'pendingCount' => collect($requests)
                ->whereIn('status', [DataExportStatus::Pending->value, DataExportStatus::Processing->value])
                ->count(),
            'deletion' => $deletionRequest === null ? null : [
                'id' => $deletionRequest->id,
                'status' => $deletionRequest->status->value,
                'status_label' => $deletionRequest->status->label(),
                'requested_at' => $deletionRequest->requested_at?->toIso8601String(),
                'effective_at' => $deletionRequest->effective_at?->toIso8601String(),
                'can_cancel' => $deletionRequest->isCancellable(),
            ],
        ]);
    }

    public function requestExport(RequestDataExportRequest $request): RedirectResponse
    {
        $this->authorize('create', DataExportRequest::class);

        $user = $request->user();

        $hasInFlight = DataExportRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [DataExportStatus::Pending->value, DataExportStatus::Processing->value])
            ->exists();

        if ($hasInFlight) {
            return back()->with('info', 'Une demande est déjà en cours de traitement.');
        }

        $exportRequest = DataExportRequest::create([
            'user_id' => $user->id,
            'status' => DataExportStatus::Pending,
        ]);

        GenerateDataExportJob::dispatch($exportRequest->id);

        return back()->with(
            'success',
            'Votre demande a été reçue. Vous recevrez un email dès que l\'archive sera prête.',
        );
    }

    public function download(DataExportRequest $export, DataExportService $service): HttpResponse|RedirectResponse
    {
        $this->authorize('download', $export);

        if (! $export->status->isDownloadable() || $this->isExpired($export)) {
            return back()->with('error', 'Cette archive n\'est plus disponible au téléchargement.');
        }

        $url = $service->downloadUrl($export);
        if ($url === null) {
            return back()->with('error', 'Impossible de générer un lien de téléchargement.');
        }

        return redirect()->away($url);
    }

    public function requestDeletion(RequestAccountDeletionRequest $request): RedirectResponse
    {
        $this->authorize('create', AccountDeletionRequest::class);

        $user = $request->user();

        $existing = AccountDeletionRequest::query()
            ->where('user_id', $user->id)
            ->where('status', AccountDeletionStatus::Pending->value)
            ->first();

        if ($existing !== null) {
            return back()->with(
                'info',
                'Une demande d\'effacement est déjà en cours pour votre compte.',
            );
        }

        AccountDeletionRequest::create([
            'user_id' => $user->id,
            'status' => AccountDeletionStatus::Pending,
            'requested_at' => now(),
            'effective_at' => now()->addDays(30),
        ]);

        Log::info('GDPR account deletion requested', [
            'user_id' => $user->id,
            'email' => $user->email,
            'structure_id' => $user->structure_id,
            'effective_at' => now()->addDays(30)->toIso8601String(),
        ]);

        return back()->with(
            'success',
            'Demande reçue. La suppression sera effective sous 30 jours sauf annulation de votre part.',
        );
    }

    public function cancelDeletion(): RedirectResponse
    {
        $user = auth()->user();

        $deletion = AccountDeletionRequest::query()
            ->where('user_id', $user->id)
            ->where('status', AccountDeletionStatus::Pending->value)
            ->first();

        if ($deletion === null) {
            return back()->with('info', 'Aucune demande d\'effacement à annuler.');
        }

        $this->authorize('cancel', $deletion);

        if (! $deletion->isCancellable()) {
            return back()->with(
                'error',
                'Le délai d\'annulation est dépassé — votre demande va être traitée par le prochain cycle.',
            );
        }

        $deletion->update([
            'status' => AccountDeletionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        Log::info('GDPR account deletion cancelled', [
            'user_id' => $user->id,
            'deletion_request_id' => $deletion->id,
        ]);

        return back()->with('success', 'Votre demande d\'effacement a été annulée.');
    }

    private function isExpired(DataExportRequest $request): bool
    {
        return $request->expires_at !== null && $request->expires_at->isPast();
    }
}
