<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PredictionRequest;
use App\Services\MLPredictionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Polls the ML microservice for a result and finalises the PredictionRequest.
 *
 * The job re-queues itself (with a delay) when the ML service is still
 * processing, so no external scheduler is needed. Maximum poll depth is
 * controlled by $tries — at 3-minute intervals × 20 tries = 1 hour max.
 *
 * Idempotent: MLPredictionService::finalise() is a no-op for terminal records.
 */
class ProcessPredictionResultJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 20;

    /** Seconds between poll attempts. */
    public int $backoff = 180;

    public bool $dispatchAfterCommit = true;

    public function __construct(public readonly PredictionRequest $predictionRequest) {}

    public function handle(MLPredictionService $service): void
    {
        Log::info('Polling ML prediction result', [
            'prediction_request_id' => $this->predictionRequest->id,
            'ml_job_id' => $this->predictionRequest->ml_job_id,
        ]);

        $finalised = $service->finalise($this->predictionRequest->fresh());

        if (! $finalised) {
            // Re-release back onto the queue to poll again later.
            $this->release($this->backoff);
        }
    }
}
