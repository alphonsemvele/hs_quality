<?php

declare(strict_types=1);

namespace App\Services\CircuitBreaker;

use RuntimeException;

class CircuitOpenException extends RuntimeException
{
    public function __construct(public readonly string $circuitName)
    {
        parent::__construct("Circuit '{$circuitName}' is open — failing fast.");
    }
}
