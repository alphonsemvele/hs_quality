<?php

declare(strict_types=1);

use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\CircuitBreaker\CircuitOpenException;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

it('passes the callable result through when the breaker is closed', function (): void {
    $breaker = new CircuitBreaker('test', threshold: 3, cooldownSeconds: 60);

    $result = $breaker->call(fn (): string => 'ok');

    expect($result)->toBe('ok');
    expect($breaker->isOpen())->toBeFalse();
});

it('rethrows the original exception on a failed call (closed state)', function (): void {
    $breaker = new CircuitBreaker('test', threshold: 3, cooldownSeconds: 60);

    expect(fn () => $breaker->call(function (): void {
        throw new RuntimeException('boom');
    }))->toThrow(RuntimeException::class, 'boom');

    expect($breaker->isOpen())->toBeFalse();
});

it('opens after the threshold of consecutive failures and fails fast thereafter', function (): void {
    $breaker = new CircuitBreaker('test', threshold: 3, cooldownSeconds: 60);

    foreach (range(1, 3) as $_) {
        try {
            $breaker->call(function (): void {
                throw new RuntimeException('upstream failure');
            });
        } catch (RuntimeException) {
        }
    }

    expect($breaker->isOpen())->toBeTrue();

    expect(fn () => $breaker->call(fn () => 'should not run'))
        ->toThrow(CircuitOpenException::class);
});

it('resets failure count on a successful call', function (): void {
    $breaker = new CircuitBreaker('test', threshold: 3, cooldownSeconds: 60);

    try {
        $breaker->call(function (): void {
            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
    }
    try {
        $breaker->call(function (): void {
            throw new RuntimeException('boom');
        });
    } catch (RuntimeException) {
    }

    $breaker->call(fn (): string => 'ok');

    foreach (range(1, 2) as $_) {
        try {
            $breaker->call(function (): void {
                throw new RuntimeException('boom');
            });
        } catch (RuntimeException) {
        }
    }

    expect($breaker->isOpen())->toBeFalse();
});

it('closes when the cooldown elapses (the next call probes recovery)', function (): void {
    $breaker = new CircuitBreaker('test', threshold: 2, cooldownSeconds: 1);

    foreach (range(1, 2) as $_) {
        try {
            $breaker->call(function (): void {
                throw new RuntimeException('boom');
            });
        } catch (RuntimeException) {
        }
    }
    expect($breaker->isOpen())->toBeTrue();

    sleep(2);

    expect($breaker->isOpen())->toBeFalse();
    expect($breaker->call(fn (): string => 'ok'))->toBe('ok');
});

it('isolates state per circuit name', function (): void {
    $arsBreaker = new CircuitBreaker('ars', threshold: 2, cooldownSeconds: 60);
    $mailBreaker = new CircuitBreaker('mail', threshold: 2, cooldownSeconds: 60);

    foreach (range(1, 2) as $_) {
        try {
            $arsBreaker->call(function (): void {
                throw new RuntimeException('boom');
            });
        } catch (RuntimeException) {
        }
    }

    expect($arsBreaker->isOpen())->toBeTrue();
    expect($mailBreaker->isOpen())->toBeFalse();
});

it('resolves named circuits from config (ars uses tighter threshold)', function (): void {
    $breaker = CircuitBreaker::for('ars');

    expect($breaker->cooldownSeconds())->toBe(300);
});

it('falls back to defaults for unknown circuit names', function (): void {
    $breaker = CircuitBreaker::for('not-configured');

    expect($breaker->cooldownSeconds())->toBe((int) config('circuit_breaker.default_cooldown_seconds'));
});
