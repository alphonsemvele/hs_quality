<?php

/**
 * Health endpoints for ALB/ECS probes. /live = process alive,
 * /ready = dependencies reachable. Unauthenticated, no tenant.
 */

it('liveness endpoint returns ok', function () {
    $response = $this->getJson('/health/live');

    $response->assertOk()
        ->assertJsonStructure(['status', 'app', 'env', 'timestamp'])
        ->assertJsonPath('status', 'ok');
});

it('readiness endpoint reports on each dependency', function () {
    $response = $this->getJson('/health/ready');

    $response->assertJsonStructure([
        'status',
        'checks' => ['database', 'redis', 'storage'],
        'timestamp',
    ]);
});
