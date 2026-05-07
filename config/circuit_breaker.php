<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default thresholds
    |--------------------------------------------------------------------------
    |
    | Used when a circuit name has no explicit override below. Tighten per
    | circuit if a specific external service has tighter SLAs.
    |
    */

    'default_threshold' => env('CIRCUIT_BREAKER_THRESHOLD', 5),
    'default_cooldown_seconds' => env('CIRCUIT_BREAKER_COOLDOWN', 60),

    /*
    |--------------------------------------------------------------------------
    | Per-circuit overrides
    |--------------------------------------------------------------------------
    |
    | `ars` — ARS regulatory notification (CDC §6.3, 24h deadline). 3
    | failures opens the breaker and we wait 5 min for recovery — short
    | enough to still hit the deadline through retries, long enough to
    | not pummel a recovering service.
    |
    */

    'circuits' => [
        'ars' => [
            'threshold' => 3,
            'cooldown_seconds' => 300,
        ],
    ],
];
