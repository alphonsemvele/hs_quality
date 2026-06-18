<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PredictionStatus;
use App\Enums\PredictionType;
use App\Models\PredictionRequest;
use App\Models\Structure;
use App\Models\User;
use App\Services\CircuitBreaker\CircuitOpenException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Orchestrates the ML prediction lifecycle.
 *
 * request()  → creates a PredictionRequest (en_attente) and queues a job.
 * process()  → called by the job; submits to MLServiceClient, transitions
 *              status to en_traitement. If the circuit is OPEN, the record
 *              stays en_attente — the job will retry and eventually succeed
 *              once the circuit recovers.
 * finalise() → called by ProcessPredictionResultJob; stores result/error.
 * latestFor()→ returns the most recent request for a given type (any status).
 */
class MLPredictionService
{
    public function __construct(private readonly MLServiceClient $client) {}

    /**
     * Create a new prediction request and dispatch the job.
     *
     * The dispatch is outside the transaction so the job worker sees the
     * committed row (dispatchAfterCommit is set on the job class).
     */
    public function request(
        Structure $structure,
        User $requester,
        PredictionType $type,
        array $inputData,
    ): PredictionRequest {
        return DB::transaction(function () use ($structure, $requester, $type, $inputData): PredictionRequest {
            return PredictionRequest::create([
                'structure_id' => $structure->id,
                'requested_by_user_id' => $requester->id,
                'type' => $type->value,
                'status' => PredictionStatus::EnAttente->value,
                'input_snapshot' => $inputData,
                'requested_at' => now(),
            ]);
        });
    }

    /**
     * Submit the request to the ML service and record the ml_job_id.
     *
     * Called by DispatchPredictionJob. If the circuit is OPEN the method
     * returns early — the request stays en_attente and the job will be
     * retried, so no action needed here.
     *
     * @throws RuntimeException On non-circuit ML service errors.
     */
    public function process(PredictionRequest $request): void
    {
        if (! $request->isPending()) {
            return; // already picked up; idempotent
        }

        try {
            $mlJobId = $this->client->submit($request->type, $request->input_snapshot);
        } catch (CircuitOpenException) {
            Log::info('ML circuit open — prediction left en_attente', [
                'prediction_request_id' => $request->id,
            ]);

            return; // remain en_attente; job retry will pick it up later
        }

        $request->update([
            'status' => PredictionStatus::EnTraitement->value,
            'ml_job_id' => $mlJobId,
        ]);
    }

    /**
     * Poll the ML service for a result and finalise the record.
     *
     * Called by ProcessPredictionResultJob. Returns true when the record
     * was finalised (terminé or échoué), false when still running.
     */
    public function finalise(PredictionRequest $request): bool
    {
        if ($request->status !== PredictionStatus::EnTraitement) {
            return $request->isTerminal(); // already done; idempotent
        }

        if ($request->ml_job_id === null) {
            return false;
        }

        try {
            $result = $this->client->getResult($request->ml_job_id);
        } catch (CircuitOpenException) {
            return false; // will retry
        } catch (Throwable $e) {
            $request->update([
                'status' => PredictionStatus::Echoue->value,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return true;
        }

        if ($result === null) {
            return false; // still running
        }

        $request->update([
            'status' => PredictionStatus::Termine->value,
            'result' => $result,
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Return the most recent PredictionRequest for the given structure + type.
     * Returns null if none exists yet.
     */
    public function latestFor(Structure $structure, PredictionType $type): ?PredictionRequest
    {
        return PredictionRequest::withoutGlobalScopes()
            ->where('structure_id', $structure->id)
            ->where('type', $type->value)
            ->latest('requested_at')
            ->first();
    }
}
