<?php

declare(strict_types=1);

namespace App\Jobs\Gdpr;

use App\Enums\AccountDeletionStatus;
use App\Models\AccountDeletionRequest;
use App\Services\Gdpr\AnonymizeUserAccountService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Scheduled daily — pick up every pending GDPR deletion request whose
 * 30-day cooling-off window has elapsed and run the anonymisation. Each
 * request is processed individually so a single failure doesn't poison
 * the whole batch; the request gets flagged Failed with a reason instead
 * of bubbling up.
 *
 * Idempotent on AccountDeletionRequest.status — a re-run advances only
 * Pending rows, so a job restarted mid-batch resumes cleanly.
 */
class ProcessAccountDeletionRequestsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function handle(AnonymizeUserAccountService $service): void
    {
        AccountDeletionRequest::query()
            ->withoutGlobalScopes()
            ->where('status', AccountDeletionStatus::Pending->value)
            ->where('effective_at', '<=', now())
            ->chunkById(100, function ($requests) use ($service): void {
                foreach ($requests as $request) {
                    $this->processOne($request, $service);
                }
            });
    }

    private function processOne(AccountDeletionRequest $request, AnonymizeUserAccountService $service): void
    {
        try {
            if ($request->user === null) {
                $request->update([
                    'status' => AccountDeletionStatus::Processed,
                    'processed_at' => now(),
                    'failure_reason' => 'user_already_removed',
                ]);

                return;
            }

            $service->anonymize($request->user);

            $request->update([
                'status' => AccountDeletionStatus::Processed,
                'processed_at' => now(),
            ]);

            Log::info('GDPR account deletion processed', [
                'request_id' => $request->id,
                'user_id' => $request->user_id,
                'structure_id' => $request->structure_id,
            ]);
        } catch (Throwable $e) {
            Log::error('GDPR account deletion failed', [
                'request_id' => $request->id,
                'user_id' => $request->user_id,
                'error' => $e->getMessage(),
            ]);

            $request->update([
                'status' => AccountDeletionStatus::Failed,
                'processed_at' => now(),
                'failure_reason' => substr($e->getMessage(), 0, 500),
            ]);
        }
    }
}
