<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PredictionType;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\CircuitBreaker\CircuitOpenException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * HTTP client for the Python ML microservice (Phase 3 / M9).
 *
 * The circuit breaker wraps every outbound call. When the breaker is
 * OPEN, CircuitOpenException is thrown — callers translate that to the
 * "en calcul" fallback (request left in EnAttente) rather than an error.
 *
 * Two-step async flow:
 *   1. submit()    → Python queues the job, returns an opaque ml_job_id.
 *   2. getResult() → poll with that id; returns null while still running.
 *
 * Auth: Bearer token from config('services.ml_service.secret').
 */
class MLServiceClient
{
    private CircuitBreaker $breaker;

    public function __construct()
    {
        $this->breaker = CircuitBreaker::for('ml_service');
    }

    /**
     * Submit a prediction job.
     *
     * @param  array<string, mixed>  $payload  Input snapshot for the ML model.
     * @return string Opaque ml_job_id assigned by the Python service.
     *
     * @throws CircuitOpenException When the breaker is OPEN.
     * @throws RuntimeException On HTTP error or unexpected response shape.
     */
    public function submit(PredictionType $type, array $payload): string
    {
        return $this->breaker->call(function () use ($type, $payload): string {
            try {
                $response = Http::withToken($this->secret())
                    ->timeout($this->timeout())
                    ->post($this->baseUrl().'/predict', [
                        'type' => $type->value,
                        'payload' => $payload,
                    ])
                    ->throw();
            } catch (ConnectionException|RequestException $e) {
                throw new RuntimeException('ML service submit failed: '.$e->getMessage(), 0, $e);
            }

            $jobId = $response->json('job_id');
            if (! is_string($jobId) || $jobId === '') {
                throw new RuntimeException('ML service returned no job_id in submit response.');
            }

            return $jobId;
        });
    }

    /**
     * Poll for a result.
     *
     * Returns null when the job is still running (HTTP 202).
     * Returns the result array when complete (HTTP 200 with `result` key).
     * Throws on HTTP error.
     *
     * @throws CircuitOpenException When the breaker is OPEN.
     * @throws RuntimeException On HTTP error.
     */
    public function getResult(string $mlJobId): ?array
    {
        return $this->breaker->call(function () use ($mlJobId): ?array {
            try {
                $response = Http::withToken($this->secret())
                    ->timeout($this->timeout())
                    ->get($this->baseUrl().'/predict/'.$mlJobId);
            } catch (ConnectionException|RequestException $e) {
                throw new RuntimeException('ML service poll failed: '.$e->getMessage(), 0, $e);
            }

            if ($response->status() === 202) {
                return null; // still processing
            }

            $response->throw();

            return $response->json('result') ?? [];
        });
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.ml_service.base_url'), '/');
    }

    private function secret(): string
    {
        return (string) config('services.ml_service.secret');
    }

    private function timeout(): int
    {
        return (int) config('services.ml_service.timeout', 10);
    }
}
