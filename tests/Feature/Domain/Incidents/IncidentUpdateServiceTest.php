<?php

declare(strict_types=1);

use App\Enums\StatutIncident;
use App\Models\Incident;
use App\Services\IncidentService;
use Database\Seeders\RoleSeeder;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Wave 0 / H4 — IncidentController::update now delegates to
 * IncidentService::update, which wraps the mutation in a transaction and
 * refuses updates on closed incidents. Both behaviors are covered here.
 */
beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('updates the whitelisted fields via the service', function (): void {
    $coord = actingAsRole('coordinateur');
    $incident = Incident::factory()->forStructure($coord->structure)->create([
        'description' => 'Original',
        'lieu' => 'Salle A',
    ]);

    $this->put("/incidents/{$incident->id}", [
        'description' => 'Updated description',
        'lieu' => 'Salle B',
    ])->assertRedirect();

    $fresh = $incident->fresh();
    expect($fresh->description)->toBe('Updated description');
    expect($fresh->lieu)->toBe('Salle B');
});

it('refuses to update a closed incident at the service layer', function (): void {
    $coord = actingAsRole('coordinateur');
    $incident = Incident::factory()->forStructure($coord->structure)->create([
        'statut' => StatutIncident::Clos->value,
        'closed_at' => now(),
    ]);

    expect(fn () => app(IncidentService::class)->update($incident, ['description' => 'X']))
        ->toThrow(HttpException::class);

    expect($incident->fresh()->description)->not->toBe('X');
});
