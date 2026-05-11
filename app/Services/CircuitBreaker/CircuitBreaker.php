<?php

declare(strict_types=1);

namespace App\Services\CircuitBreaker;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cache-backed circuit breaker for outbound calls (ARS, mail, future ML ping).
 *
 * State machine — simplified two-state with cooldown:
 *   CLOSED   calls pass through; consecutive failures counted
 *   OPEN     calls fail fast with CircuitOpenException
 *
 * Transitions:
 *   CLOSED → OPEN     N consecutive failures hit `threshold`
 *   OPEN   → CLOSED   first call after `cooldown_seconds` elapsed; the
 *                     recovery probe is the call itself (success closes,
 *                     failure re-opens)
 *
 * Why simplified rather than textbook half-open with probe locks: at
 * Phase 2 scale (50 structures, low-frequency outbound to ARS / mail /
 * ML) the "first call after cooldown probes" model is correct enough
 * and far easier to reason about and test than a Redis-locked probe
 * dance. Revisit if outbound call rate makes the thundering herd a
 * real concern.
 *
 * Storage: Laravel's default cache store. In production this is Redis
 * (so all queue workers share state). In tests, `array` (per phpunit.xml).
 */
class CircuitBreaker
{
    public function __construct(
        protected readonly string $name,
        protected readonly int $threshold = 5,
        protected readonly int $cooldownSeconds = 60,
    ) {}

    /**
     * Resolve a named circuit from `config/circuit_breaker.php`. Falls
     * back to default threshold + cooldown when the circuit name is not
     * configured — the breaker still works, it just uses defaults.
     */
    public static function for(string $name): self
    {
        $config = config("circuit_breaker.circuits.{$name}", []);

        return new self(
            $name,
            threshold: $config['threshold'] ?? config('circuit_breaker.default_threshold', 5),
            cooldownSeconds: $config['cooldown_seconds'] ?? config('circuit_breaker.default_cooldown_seconds', 60),
        );
    }

    /**
     * Execute the callable through the breaker.
     *
     * @throws CircuitOpenException when the breaker is OPEN.
     * @throws Throwable original exception from the callable on failure.
     */
    public function call(callable $fn): mixed
    {
        if ($this->isOpen()) {
            throw new CircuitOpenException($this->name);
        }

        try {
            $result = $fn();
            $this->onSuccess();

            return $result;
        } catch (Throwable $e) {
            $this->onFailure();
            throw $e;
        }
    }

    public function isOpen(): bool
    {
        $openedAt = Cache::get($this->openedAtKey());
        if ($openedAt === null) {
            return false;
        }

        return (time() - (int) $openedAt) < $this->cooldownSeconds;
    }

    public function reset(): void
    {
        Cache::forget($this->failuresKey());
        Cache::forget($this->openedAtKey());
    }

    private function onSuccess(): void
    {
        $this->reset();
    }

    private function onFailure(): void
    {
        $failures = ((int) Cache::get($this->failuresKey(), 0)) + 1;
        Cache::put($this->failuresKey(), $failures, $this->cooldownSeconds * 2);

        if ($failures >= $this->threshold) {
            Cache::put($this->openedAtKey(), time(), $this->cooldownSeconds * 2);
            Log::warning("CircuitBreaker '{$this->name}' opened", [
                'failures' => $failures,
                'threshold' => $this->threshold,
                'cooldown_seconds' => $this->cooldownSeconds,
            ]);
        }
    }

    private function failuresKey(): string
    {
        return "cb:{$this->name}:failures";
    }

    private function openedAtKey(): string
    {
        return "cb:{$this->name}:opened_at";
    }

    public function name(): string
    {
        return $this->name;
    }

    public function cooldownSeconds(): int
    {
        return $this->cooldownSeconds;
    }
}
