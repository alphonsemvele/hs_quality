<?php

declare(strict_types=1);

use App\Enums\GraviteIncident;
use App\Jobs\NotifyARSJob;
use App\Models\Incident;
use App\Models\Structure;
use App\Models\User;
use App\Services\CircuitBreaker\CircuitBreaker;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Cache::flush();

    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);

    $this->declarant = User::factory()->forStructure($this->structure)->create([
        'type' => 'intervenant',
    ]);

    $this->incident = Incident::factory()->declaredBy($this->declarant)->grave()->create();
});

it('marks the incident as ARS-notified when the circuit is closed (default)', function (): void {
    expect($this->incident->notifie_ars_at)->toBeNull();
    expect($this->incident->gravite)->toBe(GraviteIncident::Grave);

    (new NotifyARSJob($this->incident))->handle();

    expect($this->incident->fresh()->notifie_ars_at)->not->toBeNull();
});

it('does not notify ARS when the circuit is open — the job releases itself for retry after cooldown', function (): void {
    // Force the ARS circuit open by recording threshold-many failures.
    $breaker = CircuitBreaker::for('ars');
    $arsThreshold = (int) config('circuit_breaker.circuits.ars.threshold');
    foreach (range(1, $arsThreshold) as $_) {
        try {
            $breaker->call(function (): void {
                throw new RuntimeException('simulated ARS endpoint failure');
            });
        } catch (RuntimeException) {
        }
    }
    expect($breaker->isOpen())->toBeTrue();

    (new NotifyARSJob($this->incident))->handle();

    // The breaker short-circuited the outbound call, so the incident
    // remains un-notified — exactly what we want, so a recovered ARS
    // endpoint receives a fresh attempt rather than a swallowed success.
    expect($this->incident->fresh()->notifie_ars_at)->toBeNull();
});

it('skips work when the incident has already been ARS-notified (idempotency, breaker untouched)', function (): void {
    $this->incident->update(['notifie_ars_at' => now()->subHour()]);
    $before = $this->incident->fresh()->notifie_ars_at;

    (new NotifyARSJob($this->incident))->handle();

    expect($this->incident->fresh()->notifie_ars_at->equalTo($before))->toBeTrue();
});
