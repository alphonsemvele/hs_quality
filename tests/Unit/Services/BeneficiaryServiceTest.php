<?php

use App\Models\Beneficiary;
use App\Models\Structure;
use App\Services\BeneficiaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(BeneficiaryService::class);
    $this->structure = Structure::factory()->create();
    app()->instance('current_structure', $this->structure);
});

it('creates a beneficiary in the current tenant', function () {
    $beneficiary = $this->service->create([
        'first_name' => 'Marie',
        'last_name' => 'ESSOMBA',
        'date_of_birth' => '1945-07-22',
        'gir' => 3,
    ]);

    expect($beneficiary)->toBeInstanceOf(Beneficiary::class)
        ->and($beneficiary->first_name)->toBe('Marie')
        ->and($beneficiary->last_name)->toBe('ESSOMBA')
        ->and($beneficiary->gir)->toBe('3') // gir is varchar(20) — stored as string
        ->and($beneficiary->structure_id)->toBe($this->structure->id);
});

it('updates a beneficiary and returns a fresh instance', function () {
    $beneficiary = Beneficiary::factory()->forStructure($this->structure)->create([
        'gir' => 5,
    ]);

    $updated = $this->service->update($beneficiary, ['gir' => 3]);

    expect($updated->gir)->toBe('3'); // gir is varchar(20) — stored as string
});

it('anonymizes a beneficiary and preserves the row', function () {
    $beneficiary = Beneficiary::factory()
        ->forStructure($this->structure)
        ->withMedicalNotes()
        ->create([
            'first_name' => 'Amina',
            'last_name' => 'FOFANA',
            'phone' => '+237 6 90 00 00 00',
            'address' => '45 Rue Charles-de-Gaulle, Douala',
        ]);

    $originalId = $beneficiary->id;

    $anonymized = $this->service->anonymize(
        beneficiary: $beneficiary,
        requestedBy: 'Amina FOFANA (subject request)',
        reason: 'Right to erasure per RGPD Article 17',
    );

    expect($anonymized->id)->toBe($originalId)
        ->and($anonymized->first_name)->toBe('')
        ->and($anonymized->last_name)->toBe('Bénéficiaire supprimé')
        ->and($anonymized->phone)->toBeNull()
        ->and($anonymized->address)->toBeNull()
        ->and($anonymized->medical_notes)->toBeNull()
        ->and($anonymized->allergies)->toBeNull()
        ->and($anonymized->erased_at)->not->toBeNull();
});

it('encrypts health data fields at rest', function () {
    $beneficiary = $this->service->create([
        'first_name' => 'Jean',
        'last_name' => 'KOFFI',
        'medical_notes' => 'Allergie arachide, pathologie cardiaque',
        'allergies' => 'Arachide, pollen',
    ]);

    expect($beneficiary->medical_notes)->toBe('Allergie arachide, pathologie cardiaque')
        ->and($beneficiary->allergies)->toBe('Arachide, pollen');

    $raw = DB::table('beneficiaries')->where('id', $beneficiary->id)->first();
    expect($raw->medical_notes)->not->toBe('Allergie arachide, pathologie cardiaque');
    expect($raw->allergies)->not->toBe('Arachide, pollen');
});
