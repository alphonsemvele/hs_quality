<?php

declare(strict_types=1);

use App\Models\Beneficiary;
use App\Models\Incident;
use App\Models\IncidentActionCorrective;
use App\Models\IncidentSuivi;
use App\Models\Intervention;
use App\Models\InterventionPhoto;
use App\Models\InterventionSignature;
use Database\Seeders\RoleSeeder;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Models\Audit;

/**
 * Wave 1 / C4 — every health-data model must:
 *   1. Implement the Auditable contract (so changes leave a who/when trail)
 *   2. Exclude any encrypted column from the audit payload (so PHI ciphertext
 *      doesn't accumulate redundantly in the audits table where it would be
 *      unreadable after an APP_KEY rotation)
 *
 * If this test fails, see the model that no longer follows the pattern. New
 * health-data models must be added to the lists below in this same PR.
 */
beforeEach(function (): void {
    // Auto-instrumentation is enabled in tests via phpunit.xml's
    // AUDITING_CONSOLE=true env override (Block C / #51) — has to be set
    // BEFORE any model boots, hence the env+config indirection.
    $this->seed(RoleSeeder::class);
});

it('auditable contract is implemented on every health-data model', function (string $class): void {
    expect(in_array(AuditableContract::class, class_implements($class), true))
        ->toBeTrue("$class must implement OwenIt\\Auditing\\Contracts\\Auditable");
})->with([
    Beneficiary::class,
    Intervention::class,
    Incident::class,
    IncidentSuivi::class,
    IncidentActionCorrective::class,
    InterventionPhoto::class,
    InterventionSignature::class,
]);

it('Intervention auditInclude excludes encrypted columns', function (): void {
    expect((new Intervention)->getAuditInclude())->not->toContain('report_text');
    expect((new Intervention)->getAuditInclude())->not->toContain('report_voice_transcript');
});

it('Incident auditExclude lists every encrypted column', function (): void {
    expect((new Incident)->getAuditExclude())->toContain('description');
});

it('Beneficiary auditInclude excludes every encrypted column', function (): void {
    $included = (new Beneficiary)->getAuditInclude();
    expect($included)->not->toContain('medical_notes');
    expect($included)->not->toContain('allergies');
    expect($included)->not->toContain('medical_history');
    expect($included)->not->toContain('current_treatments');
});

// ── Auto-firing behavior (Block C / #51 — fixed via AUDITING_CONSOLE=true) ──

it('audit-excludes encrypted Intervention.report_text on update', function (): void {
    $coord = actingAsRole('coordinateur');
    $beneficiary = Beneficiary::factory()->forStructure($coord->structure)->create();
    $intervention = Intervention::factory()
        ->forStructure($coord->structure)
        ->state(['beneficiary_id' => $beneficiary->id, 'intervenant_id' => $coord->id])
        ->create();

    // Update both an excluded field (report_text — encrypted PHI) AND an
    // included field (cancellation_reason). The audit row must capture
    // the included field but never the excluded one.
    $intervention->update([
        'report_text' => 'Patient described as alert. PHI here.',
        'cancellation_reason' => 'Updated by test',
    ]);

    $audit = Audit::query()
        ->where('auditable_type', Intervention::class)
        ->where('auditable_id', $intervention->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->new_values ?? [])->not->toHaveKey('report_text');
    expect($audit->new_values ?? [])->toHaveKey('cancellation_reason');
});

it('audit-excludes encrypted Incident.description on update', function (): void {
    $coord = actingAsRole('coordinateur');
    $incident = Incident::factory()->forStructure($coord->structure)->create();

    $incident->update([
        'description' => 'Sensitive incident description PHI',
        'lieu' => 'Salle B',
    ]);

    $audit = Audit::query()
        ->where('auditable_type', Incident::class)
        ->where('auditable_id', $incident->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->new_values ?? [])->not->toHaveKey('description');
    expect($audit->new_values ?? [])->toHaveKey('lieu');
});

it('logs an audit row when an IncidentSuivi is created', function (): void {
    $coord = actingAsRole('coordinateur');
    $incident = Incident::factory()->forStructure($coord->structure)->create();

    $suivi = IncidentSuivi::create([
        'structure_id' => $coord->structure_id,
        'incident_id' => $incident->id,
        'author_id' => $coord->id,
        'note' => 'Followed up with the family.',
    ]);

    $audit = Audit::query()
        ->where('auditable_type', IncidentSuivi::class)
        ->where('auditable_id', $suivi->id)
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->event)->toBe('created');
});

it('logs audit rows on corrective action create + update', function (): void {
    $coord = actingAsRole('coordinateur');
    $incident = Incident::factory()->forStructure($coord->structure)->create();

    $action = IncidentActionCorrective::create([
        'structure_id' => $coord->structure_id,
        'incident_id' => $incident->id,
        'description' => 'Train staff on incident response.',
        'responsable_id' => $coord->id,
        'echeance' => now()->addDays(7),
        'statut' => 'open',
    ]);

    $action->update(['statut' => 'done', 'realise_at' => now()]);

    $audits = Audit::query()
        ->where('auditable_type', IncidentActionCorrective::class)
        ->where('auditable_id', $action->id)
        ->orderBy('id')
        ->get();

    expect($audits)->toHaveCount(2);
    expect($audits[0]->event)->toBe('created');
    expect($audits[1]->event)->toBe('updated');
});
