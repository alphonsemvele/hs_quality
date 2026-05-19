<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PredictionRequest;
use App\Services\MLPredictionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Submits a pending PredictionRequest to the ML microservice.
 *
 * Idempotent: MLPredictionService::process() is a no-op if the request
 * is no longer en_attente (duplicate dispatch or circuit-recovery retry).
 *
 * Retries: 5 attempts with exponential back-off gives the ML service
 * ~8 minutes to recover before marking the circuit as failed — aligned
 * with the ml_service circuit cooldown (120s).
 *
 * Tenant context: PredictionRequest carries structure_id; no HTTP tenant
 * resolver needed.
 */
class DispatchPredictionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $backoff = 30;

    public bool $dispatchAfterCommit = true;

    public function __construct(public readonly PredictionRequest $predictionRequest) {}

    public function handle(MLPredictionService $service): void
    {
        Log::info('Dispatching ML prediction', [
            'prediction_request_id' => $this->predictionRequest->id,
            'type' => $this->predictionRequest->type->value,
            'structure_id' => $this->predictionRequest->structure_id,
        ]);

        $service->process($this->predictionRequest);
    }
}
